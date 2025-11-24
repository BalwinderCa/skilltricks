<?php



namespace App\Traits;

use Illuminate\Support\Str;
use App\Services\AiService;
use App\Models\AiBlogWizard;
use Illuminate\Http\Request;
use Orhanerday\OpenAi\OpenAi;
use App\Models\AiBlogWizardImage;
use App\Models\AiBlogWizardTitle;
use App\Models\AiBlogWizardArticle;
use App\Models\AiBlogWizardKeyWord;
use App\Models\AiBlogWizardOutline;
use App\Models\SubscriptionPackage;
use Illuminate\Support\Facades\Log;
use  App\Http\Services\GenerateImage;
use App\Http\Controllers\Backend\AI\ParsePromptsController;
use App\Http\Controllers\Backend\AI\GenerateContentsController;
use App\Services\Integration\IntegrationService;

trait PopulateWizardData
{
    # open ai instance for blog wizard
    public function openAiInstance()
    {
        $status   = true;
        $message  = '';
        $open_ai  = new OpenAi(openAiKey());
        $return   = [
            'status'    => $status,
            'open_ai'   => $open_ai,
            'message'   => $message
        ];
        # verify if user has access to the template [template available in subscription package]
        $user = user();
        if (isCustomer()) {
            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;
            if ($package->allow_blog_wizard == 0) {
                $return   = [
                    'status'    => $status,
                    'open_ai'   => $open_ai,
                    'message'   => localize('You are not allowed to use blog wizard')
                ];
            }
            // check package balance
            $checkBalanceData = activePackageBalance();
            if (!empty($checkBalanceData)) {
                return $return;
            }
            // check word limit
            if (availableDataCheck('words') <= 0) {
                $return   = [
                    'status'    =>  false,
                    'open_ai'   =>  $open_ai,
                    'message'   =>  localize('Your word balance is low, please upgrade you plan')
                ];
            }
            $return['activePackageHistory'] = activePackageHistory();
        }
        return $return;
    }

    # generate keywords
    public function generateKeywords(Request $request)
    {
        try {
            $user                = auth()->user();
            $checkOpenAiInstance = $this->openAiInstance();
            if ($checkOpenAiInstance['status'] == true) {
                $prompt         = $request->topic;
                // filter bad words
                $parsePromptController = new ParsePromptsController;
                $foundBadWords         = $parsePromptController->filterBadWords($request->all());
                if ($foundBadWords != '') {
                    $prompt       = "bad_words_found_#themeTags" . $foundBadWords;
                    if (preg_match("/bad_words_found/i", $prompt) == 1) {
                        $badWords =  explode('_#themeTags', rtrim($prompt, ","));
                        $data = [
                            'status'  => 400,
                            'success' => false,
                            'message' => localize('Please remove these words from your inputs') . '-' . $badWords[1],
                        ];
                        return $data;
                    }
                }
                # ai prompt
                $prompt = "Generate $request->number_of_results seo friendly keywords in $request->lang language based on this topic: $request->topic, each keywords must be an array element, give the output as an array. As an examples :['keywords']. Do not add any additional heading or texts like - 'here is your...' or any other additional text. provide just the array output.";
                
                # apply Ai model
                $model  = getSubscriptionBasedModel(getDefaultModelBasedOnAiEngine());
                

                # make api call to openAi  
                $integrationService = new IntegrationService();

                $request->merge([
                    'content_type'  => 'blog_wizard_keywords'
                ]); 
                
                session()->put('model', $model);
                session()->put('system_command', "You are a creative assistant.");
                session()->put('prompt', $prompt);
                session()->put('num_of_results', 1);

                $result = $integrationService->contentGenerator(aiBlogWizardEngine(), $request); 

                // Log::info('generate Keywords' . $result);
                
                $outputContents = '';

                if ($result['success'] == true) { 

                    $prompt_tokens     = $result["promptsToken"];
                    $completion_tokens = $result["completionToken"];
                    $tokens            = $result["tokens"];  
                    
                    $outputContentsData      = $result["outputContents"];
                    $outputContentsFormatted = $this->formatOutputData($outputContentsData);
                    
                    if (is_array($outputContentsFormatted)) {
                        $outputContents = $outputContentsFormatted;
                    } elseif (is_string($outputContentsFormatted)) {
                        $outputContents = json_decode($outputContentsFormatted);
                    } else {
                        $outputContents = json_decode($outputContentsFormatted);
                    
                    }
                    Log::info('generate Keywords outputContents', $outputContents);

                    if (!empty($request->ai_blog_wizard_id)) {
                        $keyword        = AiBlogWizardKeyWord::where('ai_blog_wizard_id', $request->ai_blog_wizard_id)->first();
                        if (is_array($keyword->values)) {
                            $oldValues  = $keyword->values;
                        } elseif (is_string($keyword->values)) {
                            $oldValues  = json_decode($keyword->values);
                        } else {
                            $oldValues  = [];
                        }
                        $aiBlogWizard               = $keyword->aiBlogWizard;
                        if (is_null($aiBlogWizard)) {
                            $aiBlogWizard           = $this->__newBlogWizard($user);
                            $keyword->ai_blog_wizard_id = $aiBlogWizard->id;
                        }
                        $values                     = array_merge($oldValues, $outputContents);
                        $keyword->topic             = $request->topic;
                        $keyword->num_of_copies     += 1;
                        $keyword->values            = json_encode($values);
                    } else {
                        $aiBlogWizard               = $this->__newBlogWizard($user);
                        $keyword                    = new AiBlogWizardKeyWord;
                        $keyword->created_by        = $user->id;
                        $keyword->ai_blog_wizard_id = $aiBlogWizard->id;
                        $keyword->topic             = $request->topic;
                        $keyword->values            = json_encode($outputContents);
                    }

                    $keyword->completion_tokens     += $completion_tokens;
                    $keyword->prompt_tokens         += $prompt_tokens;
                    $keyword->total_words           += $tokens;
                    $keyword->save();

                    $aiBlogWizard->completed_step       = 1;
                    $aiBlogWizard->completion_tokens    += $keyword->completion_tokens;
                    $aiBlogWizard->prompt_tokens        += $keyword->prompt_tokens;
                    $aiBlogWizard->total_words          += $keyword->total_words;
                    $aiBlogWizard->save();

                    # 8. update word limit for user or admin/staff
                    $generateContentsController = new GenerateContentsController;
                    $generateContentsController->updateUserWords($completion_tokens, $user);

                    $data = [
                        'status'                    => 200,
                        'success'                   => true,
                        'ai_blog_wizard_id'         => $keyword->ai_blog_wizard_id ?? '',
                        'keywords'                  => json_decode($keyword->values) ?? [],
                        'output'                    => view('backend.pages.blogWizard.stepsData.keywords', ['keywords' => json_decode($keyword->values) ?? []])->render(),
                        'usedPercentage'            => view('backend.pages.templates.inc.used-words-percentage')->render(),
                    ];
                    return $data;
                } else { 
                    $message = $result['message'];
                    $data = [
                        'status'  => 400,
                        'success' => false,
                        'message' => $message
                    ];
                    return $data;
                }
            } else {
                return $checkOpenAiInstance;
            }
        } catch (\Throwable $e) {
            return [
                "status"  => false,
                "code"    => 500,
                "message" => $e->getMessage(),
                "data"    => errorArray($e)
            ];
        }
    }



    # new blog wizard

    private function __newBlogWizard($user)
    {
        $aiBlogWizard = new AiBlogWizard;
        $aiBlogWizard->user_id      = $user->id;
        $aiBlogWizard->created_by   = $user->id;
        $aiBlogWizard->uuid         = Str::uuid();
        $aiBlogWizard->save();
        return $aiBlogWizard;
    }



    # generate titles

    public function generateTitles(Request $request)
    {
        try {
            $user                = auth()->user();
            $checkOpenAiInstance = $this->openAiInstance();
            if ($checkOpenAiInstance['status'] == true) {
                // generate here
                $prompt         = "Topic: $request->topic. Keywords: $request->keywords";
                // filter bad words
                $parsePromptController = new ParsePromptsController;
                $foundBadWords = $parsePromptController->filterBadWords($request->all());
                if ($foundBadWords != '') {
                    $prompt = "bad_words_found_#themeTags" . $foundBadWords;
                    if (preg_match("/bad_words_found/i", $prompt) == 1) {
                        $badWords =  explode('_#themeTags', rtrim($prompt, ","));
                        $data = [
                            'status'  => 400,
                            'success' => false,
                            'message' => localize('Please remove these words from your inputs') . '-' . $badWords[1],
                        ];
                        return $data;
                    }
                }
                # ai prompt
                $prompt = "Generate $request->number_of_results SEO friendly titles in $request->lang language based on these topic & keywords. Topic: $request->topic, Keywords: $request->keywords, each titles must be an array element, give the output as an array.
                As an examples :['title']. Do not add any additional heading or texts like - 'here is your...' or any other additional text. provide just the array output.";
                # apply Ai model
                $model  = getSubscriptionBasedModel(getDefaultModelBasedOnAiEngine());
                

                # make api call to openAi  
                $integrationService = new IntegrationService();

                $request->merge([
                    'content_type'  => 'blog_wizard_keywords'
                ]); 
                
                session()->put('model', $model);
                session()->put('system_command', "You are a creative assistant.");
                session()->put('prompt', $prompt);
                session()->put('num_of_results', 1);

                $result = $integrationService->contentGenerator(aiBlogWizardEngine(), $request); 

                // Log::info('generate titles outputContents' . $result); 
                $outputContents = '';

                if ($result['success'] == true) { 
                    
                    $prompt_tokens     = $result["promptsToken"];
                    $completion_tokens = $result["completionToken"];
                    $tokens            = $result["tokens"];  
                    
                    $outputContentsData      = $result["outputContents"];
                    $outputContentsFormatted = $this->formatOutputData($outputContentsData);

                    if (is_array($outputContentsFormatted)) {
                        $outputContents = $outputContentsFormatted;
                    } elseif (is_string($outputContentsFormatted)) {
                        $outputContents = json_decode($outputContentsFormatted);
                    } else {
                        $outputContents = json_decode($outputContentsFormatted);
                    }
                    Log::info('generate titles outputContents', $outputContents);

                    if (!empty($request->ai_blog_wizard_id)) {
                        $aiBlogWizard               = AiBlogWizard::whereId($request->ai_blog_wizard_id)->first();
                        $title                      = AiBlogWizardTitle::where('ai_blog_wizard_id', $request->ai_blog_wizard_id)->first();
                        if (!empty($title)) {
                            if (is_array($title->values)) {
                                $oldValues  = $title->values;
                            } elseif (is_string($title->values)) {
                                $oldValues  = json_decode($title->values);
                            } else {
                                $oldValues  = [];
                            }
                            $values                     = array_merge($oldValues, $outputContents);
                            $title->num_of_copies       += 1;
                            $title->values              = json_encode($values);
                        } else {
                            $title                    = new AiBlogWizardTitle;
                            $title->values            = json_encode($outputContents);
                            $title->created_by        = $user->id;
                            $title->ai_blog_wizard_id = $aiBlogWizard->id;
                        }
                    } else {
                        $aiBlogWizard               = $this->__newBlogWizard($user);
                        $title                      = new AiBlogWizardTitle;
                        $title->values              = json_encode($outputContents);
                        $title->created_by          = $user->id;
                        $title->ai_blog_wizard_id   = $aiBlogWizard->id;
                    }

                    $title->topic             = $request->topic;
                    $title->keywords          = $request->keywords;
                    $title->completion_tokens += $completion_tokens;
                    $title->prompt_tokens     += $prompt_tokens;
                    $title->total_words       += $completion_tokens;
                    $title->save();

                    $aiBlogWizard->completed_step       = 2;
                    $aiBlogWizard->prompt_tokens        += $prompt_tokens;
                    $aiBlogWizard->completion_tokens    += $completion_tokens;
                    $aiBlogWizard->total_words          += $title->total_words;
                    $aiBlogWizard->save();

                    # 8. update word limit for user
                    $generateContentsController = new GenerateContentsController;
                    $generateContentsController->updateUserWords($completion_tokens, $user);
                    $data = [
                        'status'                    => 200,
                        'success'                   => true,
                        'ai_blog_wizard_id'         => $title->ai_blog_wizard_id ?? '',
                        'titles'                    => json_decode($title->values),
                        'output'                    => view('backend.pages.blogWizard.stepsData.titles', ['titles' => json_decode($title->values)])->render(),
                        'usedPercentage'            => view('backend.pages.templates.inc.used-words-percentage')->render(),
                    ];
                    return $data;
                } else { 
                    $message = $result['message'];
                    $data = [
                        'status'  => 400,
                        'success' => false,
                        'message' => $message
                    ];
                    return $data;
                }
            } else {
                return $checkOpenAiInstance;
            }
        } catch (\Throwable $e) {

            return [
                'status'  => 404,
                'success' => false,
                'message' => errorArray($e)
            ];
        }
    }



    # generate images

    public function generateImages(Request $request)
    {
        try {
            if (env('DEMO_MODE') == "On") {
                $data = [
                    'status'  => false,
                    'success' => false,
                    'message' => localize('Image generation is turned off in demo')
                ];
                return $data;
            }
            $user                = auth()->user();
            $checkOpenAiInstance = $this->openAiInstance();

            if ($checkOpenAiInstance['status'] == true) {
                # verify if user has access [available in subscription package]
                if (isCustomer()) {
                    // check package balance
                    $checkBalanceData = activePackageBalance('allow_images');
                    if (!empty($checkBalanceData)) {
                        return $checkBalanceData;
                    }
                    // check images limit
                    if (availableDataCheck('images')  < (int)$request->num_of_results) {
                        $data = [
                            'status'  => 400,
                            'success' => false,
                            'message' => localize('Your limit is low, please upgrade you plan'),
                        ];
                        return $data;
                    }
                }



                # get image generator type
                $imageGenerateType = getSetting('generate_image_option') ?? 'dall_e_2';
                # service class
                $generateImageService = new GenerateImage();
                if ($imageGenerateType == 'dall_e_2' || $imageGenerateType == 'dall_e_3') {
                    $generateImage = $generateImageService->generateImageByOpenAi($request);
                } elseif ($imageGenerateType == 'stable_diffusion') {
                    $generateImage = $generateImageService->generateImageByOpenAiStableDiffusion($request->merge(
                        [
                            'type' => 'text-to-image',
                            "diffusion_samples" => "none",
                            "preset" => "NONE",
                            "negative_prompts" => null,
                            "titleImage" => null,
                            "titleUpscale" => null,
                        ]
                    ));
                }
                if (!empty($generateImage)) {
                    if (!empty($generateImage['files'])) {
                        foreach ($generateImage['files'] as $key => $fileInfo) {
                            $aiBlogWizardImage = new AiBlogWizardImage;
                            $aiBlogWizardImage->storage_type        = getSetting('active_storage') ?? 'local';
                            $aiBlogWizardImage->created_by          = $user->id;
                            $aiBlogWizardImage->ai_blog_wizard_id   = $request->ai_blog_wizard_id;
                            $aiBlogWizardImage->title               = $request->title . '-' . ($key + 1);
                            $aiBlogWizardImage->resolution          = $generateImage['resolution'];
                            $aiBlogWizardImage->value               = $fileInfo['file_path'];
                            $aiBlogWizardImage->save();
                        }
                    }

                    if ($generateImage['success'] == true) {
                        try {
                            $aiBlogWizard                  = AiBlogWizard::whereId($request->ai_blog_wizard_id)->first();
                            $aiBlogWizard->completed_step  = 3;
                            $aiBlogWizard->save();
                        } catch (\Throwable $th) {
                            //throw $th;
                        }

                        # Update credit balance
                        if ($user->user_type == "customer") {
                            updateDataBalance('images', $generateImage['n'], $user);
                        }
                        $imagesArray = AiBlogWizardImage::where('ai_blog_wizard_id', $request->ai_blog_wizard_id)->latest();
                        $imageIds = $imagesArray->pluck('id');
                        $images = $imagesArray->get();

                        $data = [
                            'status'            => 200,
                            'success'           => true,
                            'imageIds'           => $imageIds,
                            'images'            => view('backend.pages.blogWizard.stepsData.images', compact('images'))->render(),
                            'usedPercentage'    => view('backend.pages.templates.inc.used-images-percentage')->render(),
                        ];
                        return $data;
                    }

                    #return if fail
                    return $generateImage;
                }
            } else {
                return $checkOpenAiInstance;
            }
        } catch (\Throwable $th) {

            return [
                'status'  => 404,
                'success' => false,
                'message' => errorArray($th)
            ];
        }
    }



    # generate outlines

    public function generateOutlines(Request $request)
    {

        try {
            $user                = auth()->user();
            $checkOpenAiInstance = $this->openAiInstance();

            if ($checkOpenAiInstance['status'] == true) {
                // generate here
                $prompt         = "Title: $request->title. Keywords: $request->keywords";
                // filter bad words
                $parsePromptController = new ParsePromptsController;
                $foundBadWords = $parsePromptController->filterBadWords($request->all());
                if ($foundBadWords != '') {
                    $prompt = "bad_words_found_#themeTags" . $foundBadWords;
                    if (preg_match("/bad_words_found/i", $prompt) == 1) {
                        $badWords =  explode('_#themeTags', rtrim($prompt, ","));
                        $data = [
                            'status'  => 400,
                            'success' => false,
                            'message' => localize('Please remove these words from your inputs') . '-' . $badWords[1],
                        ];
                        return $data;
                    }
                }



                # ai prompt
                $prompt = "Generate section headings only to write a blog in $request->lang language based on these title & keywords. Title: $request->title, Keywords: $request->keywords, each section headings must be an array element, give the output as an array. Do not add any additional heading or texts like - 'here is your...' or any other additional text. provide just the array output.";
                
                 # apply Ai model
                 $model  = getSubscriptionBasedModel(getDefaultModelBasedOnAiEngine());
                

                 # make api call to openAi  
                 $integrationService = new IntegrationService();
 
                 $request->merge([
                     'content_type'  => 'blog_wizard_keywords'
                 ]); 
                 
                 session()->put('model', $model);
                 session()->put('system_command', "You are a creative assistant.");
                 session()->put('prompt', $prompt);
                 session()->put('num_of_results', (int) $request->num_of_results);
 
                 $result = $integrationService->contentGenerator(aiBlogWizardEngine(), $request);  

                $outputContents = '';
                if ($result['success'] == true) { 
                    $prompt_tokens     = $result["promptsToken"];
                    $completion_tokens = $result["completionToken"];
                    $tokens            = $result["tokens"]; 

                    $aiBlogWizard      = AiBlogWizard::whereId($request->ai_blog_wizard_id)->first();

                    $outputContentsData      = $result["outputContents"];

                    if (is_array($outputContentsData)) {
                        foreach ($outputContentsData as $value) {
                            // new outline
                            $this->__newOutline($user, $aiBlogWizard, $value, $request->title, $request->keywords, $prompt_tokens);
                        }
                    } else {
                        $outputContents = $outputContentsData;
                        // new outline
                        $this->__newOutline($user, $aiBlogWizard, $outputContents, $request->title, $request->keywords, $prompt_tokens);
                    }
                     
                    $aiBlogWizard->completed_step      = 4;
                    $aiBlogWizard->completion_tokens   += $completion_tokens;
                    $aiBlogWizard->prompt_tokens       += $prompt_tokens;
                    $aiBlogWizard->total_words         += $tokens;
                    $aiBlogWizard->save();

                    # 8. update word limit for user
                    $generateContentsController = new GenerateContentsController;
                    $generateContentsController->updateUserWords($completion_tokens, $user);
                    $outlines = AiBlogWizardOutline::where('ai_blog_wizard_id', $aiBlogWizard->id)->latest()->get();

                    $data = [
                        'status'                    => 200,
                        'success'                   => true,
                        'ai_blog_wizard_id'         => $request->ai_blog_wizard_id ?? '',
                        'outlines'                  => $outlines,
                        'output'                    => view('backend.pages.blogWizard.stepsData.outlines', ['outlines' => $outlines])->render(),
                        'usedPercentage'            => view('backend.pages.templates.inc.used-words-percentage')->render(),
                    ];
                    return $data;
                } else {
                    $message = $result['message'];
                    $data = [
                        'status'  => 400,
                        'success' => false,
                        'message' => $message
                    ];
                    return $data;
                }
            } else {
                return $checkOpenAiInstance;
            }
        } catch (\Throwable $th) {

            return [
                'status'  => 404,
                'success' => false,
                'message' => errorArray($th)
            ];
        }
    }



    # new blog outline

    private function __newOutline($user, $aiBlogWizard, $outputContents, $title, $keywords, $prompt_tokens = 0)
    {
        $outline                    = new AiBlogWizardOutline;
        $outline->values            = $outputContents;
        $outline->created_by        = $user->id;
        $outline->ai_blog_wizard_id = $aiBlogWizard->id;
        $outline->title             = $title;
        $outline->keywords          = $keywords;
        $outlineWords               = $this->countWordFormattedOutputData($outputContents);
        $outline->prompt_tokens     = $prompt_tokens;
        $outline->completion_tokens = $this->countWordFormattedOutputData($outputContents);
        $outline->total_words       = $outlineWords;
        $outline->save();
    }



    # populate keywords data

    public function populateKeywordsData(Request $request)
    {
        $data = $request->generatedKeywords ?? [];
        return [
            'html'  => view('backend.pages.blogWizard.stepsData.keywords', ['keywords' => $data])->render()
        ];
    }



    # populate titles data

    public function populateTitlesData(Request $request)
    {
        $data = $request->generatedTitles ?? [];
        return [
            'html'  => view('backend.pages.blogWizard.stepsData.titles', ['titles' => $data])->render()
        ];
    }



    # populate images data

    public function populateImagesData(Request $request)
    {
        $data = $request->generatedImages ?? [];
        $images = AiBlogWizardImage::whereIn('id', $data)->get();
        return [
            'html'  => view('backend.pages.blogWizard.stepsData.images', compact('images'))->render()
        ];
    }



    # populate outlines data

    public function populateOutlinesData(Request $request)
    {
        $data = $request->generatedOutlines ?? [];
        return [
            'html'  => view('backend.pages.blogWizard.stepsData.outlines', ['outlines' => $data == null ? [] : $data])->render()
        ];
    }



    # populate article data

    public function populateArticleData(Request $request)
    {
        $data = $request->generatedArticle ?? null;
        $article = AiBlogWizardArticle::where('id', $data)->first();
        return [
            'html'  => view('backend.pages.blogWizard.stepsData.article', ['article' => $article])->render(),
            'id'    => $article->id ?? null,
            'contents' => $article->value ?? ""
        ];
    }

    # format ai response data
    /**
     * @incomingParams $string contains a data
     *
     * Step 1 : Check is array & not empty checking
     *
     * Step 2 : When Step 1 is true we will replace single quote with Double Quote
     *
     * Step 3 : When Step 2 is complete, now we are adding a forward-slash before "\["
     *
     * Step 4 : When Step 3 is complete, Decode the string & set empty array when it's null
     *
     * Step 5 : Finally Return the decoded data.
     *
     */

    public function formatOutputData($string)
    {
        $afterDecoded =  json_decode($string, true);
        if (is_array($afterDecoded)) {
            return $afterDecoded;
        }
        // Step 1
        if (!is_array($string) && !empty($string)) {
            // Step 2
            $jsonString = str_replace("'", "\"", $string);
            // Step 3
            $isArrayStr = preg_match('/\[(.*)\]/', $string);
            // Step 4
            if ($isArrayStr) {
                // Decode the JSON string to an array
                $afterDecoded =  json_decode($jsonString, true) ?? [];
                return $afterDecoded;
            } else {
                $jsonString = str_replace("\n  ", "", $jsonString);
                $afterDecoded =  json_decode($jsonString, true) ?? [];
                return  $afterDecoded;
            }
            // Step 5
            return  [];
        }

        // Step 5

        return $string;
    }

    public function countWordFormattedOutputData($data)
    {
        $outlineWords = 0;
        if (is_array($data) == true) {
            array_walk_recursive($data, function ($val) use (&$outlineWords) {
                $outlineWords += countWords($val);
            });
        }
        if (is_string($data) == true) {
            $outlineWords = countWords($data);
        }
        return $outlineWords;
    }
}
