<?php

namespace App\Http\Controllers\Backend\AI;

use App\Http\Controllers\Controller;

class ParsePromptsController extends Controller
{
    # get prompts
    public function index($data)
    {
        $prompt = '';
        $template_code = $data['template_code']; 

        // filter bad words
        $foundBadWords = $this->filterBadWords($data);

        if ($foundBadWords != '') {
            return "bad_words_found_#themeTags" . $foundBadWords;
        } 

        // very very important -- [todo::] change if template name/code is changed in excel sheet
        switch ($template_code) {
            case 'blog-section':
                $prompt .= 'Write a complete article' . ' in ' . $data["lang"] . ' language' . ' on this topic' . ":\n\n" .
                    strip_tags($data['title']) . "\n\n" .
                    'Use following keywords in the article' . ":\n" .
                    strip_tags($data['key_points']) . "\n\n";
                break;

            case 'blog-ideas':
                $prompt .= 'Write interesting blog ideas' . ' in ' . $data["lang"] . ' language' . ' based on this text' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'blog-title':
                $prompt .= 'Generate 10 appropriate blog titles' . ' in ' . $data["lang"] . ' language' . ' based on this text' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'blog-intro':
                $prompt .= 'Write an interesting blog post intro' . ' in ' . $data["lang"] . ' language' . ' based on this text' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n" .
                    'Blog post title' . ":\n" .
                    strip_tags($data['title']) . "\n\n";
                break;

            case 'blog-conclusion':
                $prompt .= 'Write an interesting blog conclusion' . ' in ' . $data["lang"] . ' language' . ' based on this text' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n" .
                    'Blog post title' . ":\n" .
                    strip_tags($data['title']) . "\n\n";
                break;

            case 'blog-tags':
                $prompt .= 'Write blog tags' . ' in ' . $data["lang"] . ' language' . ' based on this text' . ":\n\n" .
                    strip_tags($data['about']);
                break;

            case 'blog-summary':
                $prompt .= 'Write blog summary' . ' in ' . $data["lang"] . ' language' . ' based on this text' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'confirmation-email':
                $prompt .= 'Write a confirmation email' . ' in ' . $data["lang"] . ' language' . ' based on this text' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n" .
                    'Recipient name' . ":\n" .
                    $data['name'] . "\n\n";
                break;

            case 'discount-email':
                $prompt .= 'Write a discount email' . ' in ' . $data["lang"] . ' language' . ' based on this text' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'testimonial-email':
                $prompt .= 'Write a testimonial email' . ' in ' . $data["lang"] . ' language' . ' based on this text' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n" .
                    'Recipient name' . ":\n" .
                    $data['name'] . "\n\n";
                break;

            case 'promotional-email':
                $prompt .= 'Write a promotional email' . ' in ' . $data["lang"] . ' language' . ' based on this text' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n" .
                    'Recipient name' . ":\n" .
                    $data['name'] . "\n\n";
                break;

            case 'follow-up-email':
                $prompt .= 'Write a follow up email' . ' in ' . $data["lang"] . ' language' . ' based on this text' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'twitter-post':
                $prompt .= 'Write a tweet' . ' in ' . $data["lang"] . ' language' . ' to post in twitter based on this text' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'discount-promotion':
                $prompt .= 'Write a catchy promotional article' . ' in ' . $data["lang"] . ' language' . ' to give discount based on this text' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n" .
                    'Title of the promotion is' . ":\n" .
                    $data['title'] . "\n\n";
                break;

            case 'social-media-bio':
                $prompt .= 'Write bio' . ' in ' . $data["lang"] . ' language' . ' for social media using following keywords' . ":\n" .
                    strip_tags($data['key_points']);
                break;

            case 'facebook-ads':
                $prompt .= 'Write a Facebook Ads description' . ' in ' . $data["lang"] . ' language' . ' that makes your ad stand out and generates leads. Target audience' . ":\n" .
                    strip_tags($data['audience']) . "\n\n" .
                    'Product name' . ":\n" .
                    $data['name'] . "\n\n" .
                    'Product description' . ":\n" .
                    $data['description'] . "\n\n";
                break;

            case 'instagram-captions':
                $prompt .= 'Write 10 captions' . ' in ' . $data["lang"] . ' language' . ' for instagram post based on this text' . ":\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'social-media-post':
                $prompt .= 'Write a complete social media post' . ' in ' . $data["lang"] . ' language' . ' based on this text' . ":\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'event-promotion':
                $prompt .= 'Write a catchy promotional article' . ' in ' . $data["lang"] . ' language' . ' for an event based on this text' . ":\n" .
                    strip_tags($data['about']) . "\n\n" .
                    'Event title' . ":\n" .
                    $data['title'] . "\n\n";
                break;

            case 'google-ads-headlines':
                $prompt .= 'Write 10 catchy headlines' . ' in ' . $data["lang"] . ' language' . ' to promote your product with Google Ads. Target audience' . ":\n" .
                    strip_tags($data['audience']) . "\n\n" .
                    'Product name' . ":\n" .
                    $data['name'] . "\n\n" .
                    'Product description' . ":\n" .
                    $data['description'] . "\n\n";
                break;

            case 'google-ads-description':
                $prompt .= 'Write a Google Ads description' . ' in ' . $data["lang"] . ' language' . ' that makes your ad stand out and generates leads. Target audience' . ":\n" .
                    strip_tags($data['audience']) . "\n\n" .
                    'Product name' . ":\n" .
                    $data['name'] . "\n\n" .
                    'Product description' . ":\n" .
                    $data['description'] . "\n\n";
                break;

            case 'youtube-video-title':
                $prompt .= 'Write compelling YouTube video title' . ' in ' . $data["lang"] . ' language' . ' for the provided video description to get people interested in watching' . ":\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'youtube-video-description':
                $prompt .= 'Write compelling YouTube description' . ' in ' . $data["lang"] . ' language' . ' for the provided video description to get people interested in watching' . ":\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'youtube-video-tag-generator':
                $prompt .= 'Generate SEO-optimized YouTube tags and keywords' . ' in ' . $data["lang"] . ' language' . ' based on this text' . ":\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'website-faq':
                $prompt .= 'Generate list of 10 frequently asked questions' . ' in ' . $data["lang"] . ' language' . ' based on this text' . ":\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'website-faq-answers':
                $prompt .= 'Write answer' . ' in ' . $data["lang"] . ' language' . ' for this faq question' . ":\n" .
                    strip_tags($data['question']) . "\n\n";
                break;


            case 'website-review':
                $prompt .= 'Write review' . ' in ' . $data["lang"] . ' language' . ' to submit on a website based on this text' . ":\n" .
                    strip_tags($data['description']) . "\n\n" .
                    'Product name' . ":\n" .
                    $data['name'] . "\n\n";
                break;

            case 'website-title':
                $prompt .= 'Write title' . ' in ' . $data["lang"] . ' language' . ' for a website based on this text' . ":\n" .
                    strip_tags($data['about']) . "\n\n";
                break;


            case 'website-meta-tags':
                $prompt .= 'Write meta keywords, meta title, meta description, meta author' . ' in ' . $data["lang"] . ' language' . ' for a website based on this text' . ":\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'website-meta-description':
                $prompt .= 'Write seo friendly meta description' . ' in ' . $data["lang"] . ' language' . ' for a website based on this text' . ":\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'website-about-us':
                $prompt .= 'Generate about us content' . ' in ' . $data["lang"] . ' language' . ' for a website based on this text' . ":\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'website-terms-and-conditions':
                $prompt .= 'Generate terms and conditions' . ' in ' . $data["lang"] . ' language' . ' for this website' . ":\n" .
                    strip_tags($data['title']) . "\n\n";
                break;

            case 'website-privacy-policy':
                $prompt .= 'Generate privacy policy' . ' in ' . $data["lang"] . ' language' . ' for this website' . ":\n" .
                    strip_tags($data['title']) . "\n\n";
                break;

            case 'vision-of-the-company':
                $prompt .= 'Generate vision' . ' in ' . $data["lang"] . ' language' . ' for this company named:' . ":\n" .
                    strip_tags($data['title']) . ":\n" .
                    'Company details:' . ":\n" .
                    strip_tags($data['about']);
                break;

            case 'mission-of-the-company':
                $prompt .= 'Generate mission' . ' in ' . $data["lang"] . ' language' . ' for this company named:' . ":\n" .
                    strip_tags($data['title']) . ":\n" .
                    'Company details:' . ":\n" .
                    strip_tags($data['about']);
                break;

            case 'academic-essay':
                $prompt .= 'Write an academic essay' . ' in ' . $data["lang"] . ' language' . ' for the title or topic:' . ":\n" .
                    strip_tags($data['title']);
                break;

            case 'article-generator':
                $prompt .= 'Write a complete article' . ' in ' . $data["lang"] . ' language' . ' on this topic' . ":\n\n" .
                    strip_tags($data['title']) . "\n\n" .
                    'Use following keywords in the article' . ":\n" .
                    strip_tags($data['key_points']) . "\n\n";
                break;

            case 'paragraph-generator':
                $prompt .= 'Write paragraph' . ' in ' . $data["lang"] . ' language' . ' on this topic' . ":\n\n" .
                    strip_tags($data['title']) . "\n\n" .
                    'Use following keywords in the article' . ":\n" .
                    strip_tags($data['key_points']) . "\n\n";
                break;

            case 'content-rewriter':
                $prompt .= 'Rewrite this content' . ' in ' . $data["lang"] . ' language' . '' . ":\n\n" .
                    strip_tags($data['contents']) . "\n\n" .
                    'Focus on the following keywords while generating the content' . ":\n" .
                    strip_tags($data['key_points']) . "\n\n";
                break;

            case 'product-description':
                $prompt .= 'Write a long creative product description' . ' in ' . $data["lang"] . ' language' . ' for' . ":\n\n" .
                    strip_tags($data['name']) . "\n\n";
                break;

            case 'product-name-generator':
                $prompt .= 'Create creative product names' . ' in ' . $data["lang"] . ' language' . ' based on the description' . ":\n\n" .
                    strip_tags($data['description']) . "\n\n";
                break;

            case 'product-summarize-text':
                $prompt .= 'Summarize this text' . ' in ' . $data["lang"] . ' language' . ' in a short concise way' . ":\n\n" .
                    strip_tags($data['description']) . "\n\n" .
                    'Product name' . ":\n" .
                    $data['name'] . "\n\n";
                break;

            case 'grammar-checker':
                $prompt .= 'Check and correct grammar of this text' . ":\n\n" .
                    strip_tags($data['contents']) . "\n\n";
                break;

            case 'creative-story':
                $prompt .= 'Generate an interesting creative story' . ' in ' . $data["lang"] . ' language' . ' based on this text' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'startup-name-generator':
                $prompt .= 'Generate start up names' . ' in ' . $data["lang"] . ' language' . ' based on this text' . ":\n\n" .
                    strip_tags($data['description']) . "\n\n";
                break;

            case 'pros-cons':
                $prompt .= 'Write pros and cons' . ' in ' . $data["lang"] . ' language' . ' of the topic' . ":\n\n" .
                    strip_tags($data['topic']) . "\n\n";
                break;

            case 'job-description':
                $prompt .= 'Write job description' . ' in ' . $data["lang"] . ' language' . ' based on the requirements' . ":\n\n" .
                    strip_tags($data['requirements']) . "\n\n" .
                    'Job position' . ":\n" .
                    $data['position'] . "\n\n";
                break;

            case 'rejection-letter':
                $prompt .= 'Write a rejection letter' . ' in ' . $data["lang"] . ' language' . ' based on this text' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n" .
                    'Recipient name' . ":\n" .
                    $data['name'] . "\n\n";
                break;

            case 'offer-letter':
                $prompt .= 'Write an offer letter' . ' in ' . $data["lang"] . ' language' . ' based on this text' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n" .
                    'Recipient name' . ":\n" .
                    $data['name'] . "\n\n";
                break;

            case 'promotion-letter':
                $prompt .= 'Write a promotion letter' . ' in ' . $data["lang"] . ' language' . '.' . "\n\n" .
                    'Recipient name' . ":\n" .
                    $data['name'] . "\n\n" .
                    'Previous position' . ":\n" .
                    $data['previous_position'] . "\n\n" .
                    'New position' . ":\n" .
                    $data['new_position'] . "\n\n";
                break;

            case 'motivational-quote':
                $prompt .= 'Write inspiring motivational quotes' . ' in ' . $data["lang"] . ' language' . ' to overcome the given situations' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'song-lyrics':
                $prompt .= 'Write full song lyrics of' . ":\n\n" .
                    strip_tags($data['title']);
                break;

            case 'short-story':
                $prompt .= 'Write a creative short story' . ' in ' . $data["lang"] . ' language' . ' based on this text' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'wedding-quote':
                $prompt .= 'Write lovely wedding quotes' . ' in ' . $data["lang"] . ' language' . ' based on these keywords' . ":\n\n" .
                    strip_tags($data['key_points']) . "\n\n";
                break;

            case 'birthday-wish-quote':
                $prompt .= 'Write birthday wish quotes' . ' in ' . $data["lang"] . ' language' . ' based on these keywords' . ":\n\n" .
                    strip_tags($data['key_points']) . "\n\n";
                break;

            case 'story-outline':
                $prompt .= 'Write the outline of the story' . ' in ' . $data["lang"] . ' language' . ' for medium.com based on this description' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'story-title-subtitle':
                $prompt .= 'Write the title & subtitle of the story' . ' in ' . $data["lang"] . ' language' . ' for medium.com based on this description' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n";
                break;


            case 'story-ideas':
                $prompt .= 'Write interesting story ideas' . ' in ' . $data["lang"] . ' language' . ' for medium.com based on these keywords' . ":\n\n" .
                    strip_tags($data['key_points']) . "\n\n";
                break;

            case 'tiktok-video-script':
                $prompt .= 'Write interesting tiktok video script' . ' in ' . $data["lang"] . ' language' . ' based on these keywords' . ":\n\n" .
                    strip_tags($data['key_points']) . "\n\n";
                break;

            case 'tiktok-video-captions':
                $prompt .= 'Write 10 catchy captions' . ' in ' . $data["lang"] . ' language' . ' for this tiktok video' . ":\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'video-ideas':
                $prompt .= 'Write interesting video ideas' . ' in ' . $data["lang"] . ' language' . ' based on these keywords' . ":\n\n" .
                    strip_tags($data['key_points']) . "\n\n";
                break;

            case 'instagram-story-ideas':
                $prompt .= 'Write interesting instagram story ideas' . ' in ' . $data["lang"] . ' language' . ' based on this description' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'instagram-post-ideas':
                $prompt .= 'Write interesting instagram post ideas' . ' in ' . $data["lang"] . ' language' . ' based on this description' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'instagram-reel-ideas':
                $prompt .= 'Write interesting instagram reel ideas' . ' in ' . $data["lang"] . ' language' . ' based on this description' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'instagram-hashtag':
                $prompt .= 'Generate 15 hashtags for instagram post' . ' in ' . $data["lang"] . ' language' . ' based on this description' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'career':
                $prompt .= 'Write success story of career' . ' in ' . $data["lang"] . ' language' . ' based on this description' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'business':
                $prompt .= 'Write success story of business' . ' in ' . $data["lang"] . ' language' . ' based on this description' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'start-up':
                $prompt .= 'Write success story of start up' . ' in ' . $data["lang"] . ' language' . ' based on this description' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n";
                break;

            case 'matrimonial-website':
                $prompt .= 'Write success story for matrimonial website' . ' in ' . $data["lang"] . ' language' . ' based on this description' . ":\n\n" .
                    strip_tags($data['about']) . "\n\n" .
                    'Partners name' . ":\n" .
                    $data['name'] . "\n\n";
                break;

            case 'blog-post-seo-meta-description':
                $prompt .= 'Write seo friendly meta description' . ' in ' . $data["lang"] . ' language' . ' for this blog' . ":\n\n" .
                    strip_tags($data['title']) . "\n\n" .
                    'Blog Description' . ":\n" .
                    $data['description'] . "\n\n";
                break;
            case 'home-page-seo-meta-description':
                $prompt .= 'Write seo friendly meta description' . ' in ' . $data["lang"] . ' language' . ' for this website' . ":\n\n" .
                    strip_tags($data['name']) . "\n\n" .
                    'Website Description' . ":\n" .
                    $data['description'] . "\n\n";
                break;
            case 'product-page-seo-meta-description':
                $prompt .= 'Write seo friendly meta description' . ' in ' . $data["lang"] . ' language' . ' for this product' . ":\n\n" .
                    strip_tags($data['name']) . "\n\n" .
                    'Product Description' . ":\n" .
                    $data['description'] . "\n\n";
                break;
            default:
                # code...
                break;
        }
        if( $data['max_tokens'] != -1){
            $prompt .= ' .The tone of voice should be ' . $data['tone'] . ' and the output must be completed in ' . $data['max_tokens'] . ' words. Do not generate translation.';
        }else{
            $prompt .= ' .The tone of voice should be ' . $data['tone'] .'. Do not generate translation.';
        }
        return $prompt;
    }

    # get prompts for images
    public function images($data)
    {
        $prompt = '';

        $title = $data['title'];
        $style = $data['style'] != 'none' ? $data['style'] : '';
        $mood = '';
        if(array_key_exists('mood', $data)){
            $mood = $data['mood'] != 'none' ? $data['mood'] : '';
        }

        $prompt .= $title . "," . $style . "," . $mood;
        return $prompt;
    }

    # get prompts for images
    public function imageToImage($data)
    {
        $prompt = '';

        $title = $data['titleImage'];
        $style = $data['style'] != 'none' ? $data['style'] : '';
        $mood = $data['mood'] != 'none' ? $data['mood'] : '';

        $prompt .= $title . "," . $style . "," . $mood;
        return $prompt;
    }
    

     # filter bad words
     public function filterBadWords($data)
     {
        $foundBadWords = '';
        if (getSetting('ai_filter_bad_words') != null && getSetting('ai_filter_bad_words') != '' && !empty(getSetting('ai_filter_bad_words'))) { 
            $badWordsArray = explode(',', getSetting('ai_filter_bad_words'));
            foreach ($data as $inputName => $inputValue) {
                if ($inputName != 'template_code') {
                    foreach ($badWordsArray as $badWord) {
                        if (preg_match("/$badWord/i", $inputValue) == 1) {
                            $foundBadWords .= $badWord . ',';
                        }
                    }
                }
            }
        }
        return $foundBadWords;
     }
}
