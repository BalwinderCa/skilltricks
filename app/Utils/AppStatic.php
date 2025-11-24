<?php

namespace App\Utils;

class AppStatic
{
    const DS = DIRECTORY_SEPARATOR;
    # Customer User Type
    const TYPE_ADMIN  = "admin";
    const TYPE_CUSTOMER  = "customer";
    const TYPE_STAFF  = "staff";


    # Mode
    const MODE_DEMO  = "On";
    const MODE_PRODUCTION  = "On";

    # voice generate
    const ELEVEN_LAB = 'eleven_labs';
    const OpenAiTTS = 'open_ai_tts';

    // Blog Wizard
    const wizardMaxToken = 600;

    // test
    const maxUpdateFile  = -5;

    const ENABLE_AFFILIATE_SYSTEM = "enable";
    const rewrite_types = [
        'rewrite' => 'Re-Write',
        'summarize' => 'Summarize',
        'make_it_longer' => "Make it longer",
        'make_it_shorter' => 'Make It Shorter',
        'improve_writing' => 'Improve Writing',
        'grammar_correction' => 'Grammatical Improvement'
    ];

    const PDF_CHAT_ROLE = "PDF EXPERT";
    const PDF_CHAT_HELP_TXT =  "I can assist you with PDF or Images-related information or questions";
    const PDF_CHAT_COMPLETION = [
        "role" => "system",
        "content" => "You are my Best AI PDF Assistant.",
    ];

    const TEMP_PDF_DIR = "uploads/pdfChats/";
    const FINE_TUNE_JSONL_DIR = "uploads/fineTuneJsonl/";

    const ENGINE_OPEN_AI = 1;
    const ENGINE_DIFFUSION = 2;

    const TRUE = true;
    const FALSE = false;

    const SUCCESS_CODE = 200;
    const INTERNAL_SERVER_ERROR = 500;
    const SUCCESS_WITH_DATA = 201;
    const SOMETHING_WENT_WRONG = 555;
    const REGISTRATION_WITH_DISABLE = 'disable';
    const defaultTheme = 'default';
    const theme1 = 'theme1';
    const limitPurchasePackage = 2;

    public const CLAUDE_AI_MODELS = [
        'claude-3-opus-20240229'    => 'Claude 3 Opus',
        'claude-3-sonnet-20240229'  => 'Claude 3.5 Sonnet',
    ];
    
    public const DEEPSEEK_AI_MODELS = [
        'deepseek-chat'    => 'DeepSeek V3',
    ];
   
    const OPEN_AI_ENGINE        = "openai";
    const CLAUDE_AI_ENGINE      = "claudeai";
    const DEEPSEEK_AI_ENGINE    = "deepseekai";

    public const ENGINE_LISTS = [
        "openai"            => 'Open AI',
        "claudeai"          => 'Claude AI',
        "deepseekai"        => 'DeepSeek AI',
    ];

}
