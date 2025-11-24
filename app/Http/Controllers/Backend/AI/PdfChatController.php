<?php

namespace App\Http\Controllers\Backend\AI;

use App\Models\PdfChat;
use App\Utils\AppStatic;
use Illuminate\Http\Request;
use Smalot\PdfParser\Parser;
use App\Services\UserService;
use App\Services\Pdf\PdfService;
use App\Traits\FileProcessTrait;
use Illuminate\Support\Facades\DB;
use App\Models\SubscriptionPackage;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\PdfChat\PdfChatStoreRequest;


class PdfChatController extends Controller
{
    use FileProcessTrait;

    public function __construct()
    {
        if (getSetting('enable_ai_pdf_chat') == '0') {
            flash(localize('AI PDF chat is not available'))->info();
            redirect()->route('writebot.dashboard')->send();
        }
    }


    public function index(Request  $request, PdfService $pdfService, AppStatic $appStatic)
    {
        if (isCustomer()) {
            $package = optional(activePackageHistory())->subscriptionPackage ?? new SubscriptionPackage;
            if ($package->allow_ai_pdf_chat == 0) {
                abort(403);
            }
        } else {
            if (!auth()->user()->can('ai_pdf_chat')) {
                abort(403);
            }
        }
        // When No Conversation Created
        if(!$pdfService->isAnyPdfChat()){
            // Set New Pdf Chat while user just browsed or clicked on New Chat Button.
            $request->ajax() ? $this->setPdfChat() : $this->setPdfChat();
        }else{
            setSession($pdfService::SESSION_PDF_CHAT_CODE, $pdfService->getPdfChats()->first()->chat_code);

        }

        // Create New Conversation
        if($request->ajax()){
            // New Chat
            if($request->newChat){

                $this->setPdfChat();

                $data["pdfChats"] = $pdfService->getPdfChats(true);
                $pdfChatLists     = view("backend.pages.pdfChat.pdf-chat-li", $data)->render();

                return apiResponse(
                    $appStatic::TRUE,
                    $appStatic::SUCCESS_WITH_DATA,
                    "New Conversation Created Successfully",
                    $pdfChatLists
                );
            }

            // Pdf Chat Conversation Load
            if($request->load_pdf_chat){

                $pdfChat = $pdfService->findByPdfChatId($request->load_pdf_chat,"conversations");

                // Pdf Session Set
                setSession($pdfService::SESSION_PDF_CHAT_CODE, $pdfChat->chat_code);

                $data["myLastPdfChat"] = $pdfChat;

                $conversations = view("backend.pages.pdfChat.pdf-chat-conversations", $data)->render();

                return apiResponse(
                    $appStatic::TRUE,
                    $appStatic::SUCCESS_WITH_DATA,
                    "Pdf Chat Conversation Loading",
                    $conversations
                );
            }
        }

        $data["pdfChats"]      = $pdfService->getPdfChats(true);
        $data["myLastPdfChat"] = $pdfService->myLastPdfChat();

        return view("backend.pages.pdfChat.index")->with($data);
     }




    public function destroy(Request  $request, AppStatic $appStatic, PdfService $pdfService)
    {
        try {
            DB::beginTransaction();

            $pdfChat = $pdfService->findByPdfChatId($request->pdf_chat_id);

            $pdfChat->delete();

            DB::commit();

            $data["pdfChats"] = $pdfService->getPdfChats(true);
            $pdfChatLists     = view("backend.pages.pdfChat.pdf-chat-li", $data)->render();

            return apiResponse(
                $appStatic::TRUE,
                $appStatic::SUCCESS_WITH_DATA,
                "A Pdf Chat has been deleted.",
                $pdfChatLists
            );

        }catch (\Throwable $e){
            DB::rollBack();

            return apiResponse(
                $appStatic::FALSE,
                $appStatic::SOMETHING_WENT_WRONG,
                "Something went Wrong",
                errorArray($e)
            );
        }
    }

    public function setPdfChat()
    {
        $pdfService = new PdfService();

        $pdfChat = $pdfService->storePdfChat([
            "chat_code" => randomStringNumberGenerator(10,true,true)
        ]);

        // Pdf Session Set
        setSession($pdfService::SESSION_PDF_CHAT_CODE, $pdfChat->chat_code);
    }

     /**
      * PDF Chat
      *
      * Step 1,2 : Generate a dynamic filename of the uploadble file ex. a1b2c3.pdf & Upload it to the desired folders & return from the trait file with file path location including file name
      * Step 3 : Parse the PDF file and get the text from it
      * Step 3.1 : Upload it to the S3 if the settings is enabled
      * Step 4 : Make embed vector from the parsed text of PDF.
      * Step 5 : Declare a parameter called $prompt and store user input prompt into $prompt variable
      * Step 6 : Make embed vector from the prompt user input.
      * Step 7 : Combine the two embed vectors and return similarity score
      * Step 8 : declare a final prompt from variable
      * Step 9 : Make a Chat Completion from OpenAI API
      * */
    public function pdfChatEmbedding(PdfChatStoreRequest $request, PdfService $pdfService, AppStatic  $appStatic)
    {
        try {
            DB::beginTransaction();

            $pdfFile = $request->file("pdfFile");

            $pdfChat = $pdfService->myLatestChat();

            // Step 1 & 2 : Generate a dynamic filename of the uploadble file ex. a1b2c3.pdf & Upload it to the desired folders & return from the trait file with file path location including file name
            $uploadedPdfFile = $this->fileProcess($pdfFile, appStatic()::TEMP_PDF_DIR,false);


            // Step 3 : Parse the PDF file and get the text from it
            $pdfBodyText = $pdfService->getText($uploadedPdfFile);
            $pageBody    = $pdfBodyText;

            // Step 3.1 : Upload it to the S3 if the settings is enabled
           //TODO::S3 Bucket Upload

            if (!mb_check_encoding($pdfBodyText, 'UTF-8')) {
                $pageBody = mb_convert_encoding($pdfBodyText, 'UTF-8', mb_detect_encoding($pdfBodyText));
            }

            // Step 4 : Make embed vector from the parsed text of PDF.
            $pdfBodyEmbed = $pdfService->getEmbeddingData($pdfBodyText);

        #    Log::info("PDF Body  Embedding : ".json_encode($pdfBodyEmbed));

            $isOpenAiRaiseError = isOpenAiRaiseError($pdfBodyEmbed);

            if(isOpenAiRaiseError($pdfBodyEmbed) !=false){
                DB::rollBack();

                return apiResponse($appStatic::FALSE, $appStatic::SOMETHING_WENT_WRONG, $isOpenAiRaiseError);
            }

            // Step 5 : Declare a parameter called $prompt and store user input prompt into $prompt variable
            $prompt = $request->prompt;

            // Step 6 : Make embed vector from the prompt user input.
            $promptEmbed = $pdfService->getEmbeddingData($prompt);

          #  Log::info("Prompt Embedding : ".json_encode($promptEmbed));

            $isOpenAiRaiseError = isOpenAiRaiseError($promptEmbed);

            if(isOpenAiRaiseError($isOpenAiRaiseError) !=false){
                DB::rollBack();

                return apiResponse($appStatic::FALSE, $appStatic::SOMETHING_WENT_WRONG, $isOpenAiRaiseError);
            }

            /**
             * ============ Store the PDF Chat Conversation Start ================
             * */

            $pdfChatConversation = $pdfService->storePdfConversation([
               "pdf_chat_id"              => $pdfChat->id,
               "prompt"                   => $prompt,
               "pdf_content"              => $pageBody,
               "pdf_file"                 => 'public/'.$uploadedPdfFile,
               "pdf_embedding_content"    => $pdfBodyEmbed,
               "prompt_embedding_content" => $promptEmbed
            ]);


        #    Log::info("Pdf Chat Conversation : ".json_encode($pdfChatConversation));

          //  commonLog("PDF Chat Conversation Stored",["conversation" => $pdfChatConversation] );

            /**
             * ============ Store the PDF Chat Conversation End ================
             * */

            $getSimilarityScore = $pdfService->getSimilarityScore($pdfChatConversation, $promptEmbed, $pdfBodyEmbed);

       #     Log::info("Similarity : ".json_encode($getSimilarityScore));

            DB::commit();

            setSession($pdfService::SESSION_PDF_CHAT_CONVERSATION, $pdfChatConversation->id);
            setSession($pdfService::SESSION_PDF_CHAT_PDF_CONTENT, $pdfChatConversation->pdf_content);
            setSession($pdfService::SESSION_PDF_CHAT_PROMPT_CONTENT, $pdfChatConversation->prompt);

            return apiResponse(
              $appStatic::TRUE,
              $appStatic::SUCCESS_WITH_DATA,
              "PDF Chat Processed Successfully",
              $pdfChatConversation
            );
        }
        catch (\Throwable $e){
            DB::rollBack();

            Log::info("Failed to Embedding PDF and action ".json_encode(errorArray($e)));

            return apiResponse(
                $appStatic::FALSE,
                $appStatic::SOMETHING_WENT_WRONG,
                $e->getMessage(),
                errorArray($e)
            );
        }
     }


    public function pdfChatCompletion(Request $request, PdfService $pdfService)
    {
        Log::info("Pdf Chat Completion Request : ".json_encode($request->all()));

        $prompt      = getSession($pdfService::SESSION_PDF_CHAT_PROMPT_CONTENT);
        $pdfBodyText = getSession($pdfService::SESSION_PDF_CHAT_PDF_CONTENT);
        $finalPrompt = $pdfService->pdfChatFinalPrompt($prompt, $pdfBodyText);

        $chatCode    = $pdfService->getPdfSessionChatCode();

        $openAi = initOpenAi();
        $user   = user();

        $opts   = $pdfService->setConfigurations([
            $pdfBodyText,
            $finalPrompt,
            $prompt
        ]);

        return response()->stream(function () use ( $openAi, $user, $opts, $prompt, $pdfService) {
            $text = "";

            Log::info("Streaming started for PDF Chat Completion");

            // Get PDF Chat Conversation
            $pdfChatConversation = $pdfService->getPdfChatConversationById(getSession($pdfService::SESSION_PDF_CHAT_CONVERSATION));

                $openAi->chat($opts, function ($curl_info, $data) use (&$text, $user, $pdfService, $pdfChatConversation ) {
                    $output = "";

                    $chatResponse = explode("data:", $data);

                    // Parse Chat Response
                    $parseChatResponse = $pdfService->parseChatResponse($chatResponse);

                    $text   .= $parseChatResponse["text"];
                    $output .= $parseChatResponse["output"];

//                    # Update credit balance
//                    $output  = str_replace(["\r\n", "\r", "\n"], "<br>", $text);

                    $payloads = [
                        "ai_response" => $text,
                        "words"       =>  count(explode(' ', $text))
                    ];

                    // Update Pdf Chat Conversation
                    $pdfService->updatePdfChatConversation($pdfChatConversation, $payloads);

                    echo $data;
                    echo "\n\n";
                    echo PHP_EOL;

                    if (ob_get_level() < 1) {
                        ob_start();
                    }
                    ob_flush();
                    flush();
                    return strlen($data);
                });


            // Updating User Wallets.
            (new UserService())->updateUserWords($pdfService->getPdfChatConversationsWords($pdfChatConversation), $user);
        }, 200, [
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Content-Type'      => 'text/event-stream',
        ]);
    }

}
