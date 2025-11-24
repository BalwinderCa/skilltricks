<?php

namespace App\Http\Controllers\Backend\AI;

use Illuminate\Http\Request;
use Orhanerday\OpenAi\OpenAi;
use App\Http\Controllers\Controller;

class ReWriteController extends Controller
{

    public function index(Request $request)
    {

        try {
            $data = [
                'status' => 400,
                'response' => 'something wrong'
            ];
            $type     = $request->type;
            $language = $request->language;
            $text = $request->text;
            $prompt = null;

            if (!$text) {
                $data = [
                    'status' => 400,
                    'response' => "I'm sorry, but you haven't provided any text. Please provide the text you would like me to work with and I'll be happy to assist you"
                ];
            }
            if ($text) {
                if ($type == 'rewrite') {
                    $prompt = "Rewrite the text  content professionally language is '$language' and text is '$text'";
                } else if ($type == 'summarize') {
                    $prompt = "Summarize the content professionally language is '$language' and text is '$text'";
                } else if ($type == 'make_it_longer') {
                    $prompt = "make it longer the content professionally language is '$language' and text is '$text'";
                } else if ($type == 'make_it_shorter') {
                    $prompt = "make it shorter the content professionally language is '$language' and text is '$text'";
                } else if ($type == 'improve_writing') {
                    $prompt = "Improve the content professionally language is '$language' and text is '$text'";
                } else if ($type == 'grammar_correction') {
                    $prompt = "Correct this to standard $language. Text is '$text'";
                }
                if ($prompt) {
                    $open_ai = new OpenAi(openAiKey());
                    $user    = auth()->user();
                    $opts    = [
                        'model'             => 'gpt-3.5-turbo',
                        'messages'          =>  [[
                            'role' => 'user',
                            'content' => $prompt
                        ]],

                    ];
                    $completion = $open_ai->chat($opts);
                    $completion = json_decode($completion);
                    if ($completion) {
                        if (property_exists($completion, 'error')) {
                            $message = $completion->error->message;
                            $data = [
                                'status' => 400,
                                'response' => $$message
                            ];
                        } else {
                            $output_text = $completion->choices[0]->message->content;
                            $data = [
                                'status' => 200,
                                'response' => $output_text
                            ];

                            if(isCustomer()) {
                                updateDataBalance('words', strlen($output_text), $user);
                            }
                        }
                    }
                  
                }
            }
            return response()->json($data);
        } catch (\Throwable $th) {
            //throw $th;
            $data = [
                'status' => 400,
                'response' => $th->getMessage()
            ];
        }
    }
}
