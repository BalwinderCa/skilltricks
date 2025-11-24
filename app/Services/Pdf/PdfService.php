<?php
namespace App\Services\Pdf;

use App\Models\PdfChat;
use App\Models\PdfChatConversation;
use Illuminate\Support\Facades\Log;
use Orhanerday\OpenAi\OpenAi;
class PdfService
{
    const SESSION_PDF_CHAT_CONVERSATION = "pdfChatConversation";
    const SESSION_PDF_CHAT_PDF_CONTENT  = "pdfChatPdfContent";
    const SESSION_PDF_CHAT_PROMPT_CONTENT  = "pdfChatPromptContent";
    CONST SESSION_PDF_CHAT_CODE = "chat_code";
    /**
     * PDF Text Collect
     *
     * @incomingParam $pdfFileWithDirectory contains a string data with directory/xyz.pdf file
     *
     * @return string
     * */
    public function getText($pdfFileWithDirectory) : string
    {
        $file = asset('public/'.$pdfFileWithDirectory);
        return initPdfParser()->parseFile($file)->getText();
    }

    public function getPdfChatConversations()
    {
        return PdfChatConversation::query()->userId(userId())->get();
    }

    public function findByPdfChatId($id, $withRelationships = [])
    {
        $query =  PdfChat::query();

        !empty($withRelationships) ? $query->with($withRelationships) : null;

        return $query->findOrFail($id);
    }

    public function loadLastPdfChatConversation()
    {
        return PdfChatConversation::query()->userId(userId())->latest()->first() ?? [];
    }

    public function myLastPdfChat()
    {
        return PdfChat::query()->with("conversations")->userId(userId())->latest()->first() ?? [];
    }

    public function isAnyPdfChat()
    {

        return $this->getPdfChats()->count() > 0;
    }

    public function getPdfChats($onlyTitleAndId = false)
    {
        $query = PdfChat::query()->userId(userId())->latest();

        return $onlyTitleAndId ? $query->get(["chat_code", "id","created_at"]) : $query->get();
    }


    /**
     * Start New Pdf Chat
     * */
    public function storePdfChat(array $payloads)
    {
        return PdfChat::query()->create($payloads);
    }


    public function getEmbeddingData($contents)
    {
        $openAi = initOpenAi();

        $embed = $openAi->embeddings([
            'input' => $contents,
            'model' => 'text-embedding-ada-002',
        ]);

        if (!empty($embed)){
            $embedJsonDecode = convertJsonDecode($embed);

            return isset($embedJsonDecode["data"], $embedJsonDecode["data"][0], $embedJsonDecode["data"][0]["embedding"][0]) ? $embedJsonDecode["data"][0]["embedding"] : $embedJsonDecode;
        }

        return null;
    }

    public function myLatestChat($user_id = null)
    {
        return PdfChat::query()->userId($user_id)->chatCode(getSession(self::SESSION_PDF_CHAT_CODE))->latest()->first();
    }


    public function storePdfConversation(array $payloads)
    {
        return PdfChatConversation::query()->create($payloads);
    }

    public function getSimilarityScore(object $pdfChatConversation,$promptEmbeddingContent, $pdfEmbeddingContent)
    {
        $results = [];
        $similarity = $this->cosineSimilarity($promptEmbeddingContent, $pdfEmbeddingContent);

        Log::info("Similarity Score Found : ".json_encode($similarity));

        $results[] = [
            'id' => 1,
            'content' => $pdfChatConversation->pdf_content,
            'similarity' => $similarity,
        ];

        usort($results, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        $result = "";
        $resArr = array_slice($results, 0, 5);
        foreach ($resArr as $item) {
            $result = $result . $item['content'] . "\n\n\n";
        }

        Log::info("Step 9 : Before calling chat completionFinal Content As Result : ".json_encode($result));

        return $result;

    }

    public function cosineSimilarity($u, $v)
    {
        $dotProduct = 0;
        $uLength = 0;
        $vLength = 0;

        for ($i = 0; $i < count($u); $i++) {
            $dotProduct += $u[$i] * $v[$i];
            $uLength += $u[$i] * $u[$i];
            $vLength += $v[$i] * $v[$i];
        }

        $uLength = sqrt($uLength);
        $vLength = sqrt($vLength);

        return $dotProduct / ($uLength * $vLength);
    }


    public function pdfChatFinalPrompt($userPrompt, $pdfContent)
    {
        $sofliq =  "'this pdf' means pdf content. Do not reference previous chats, when user asking about pdf. Include reference pdf content when user only ask about the pdf. Otherwise response as assistant in short and professional and don't refere the pdf content. \n\n\n User Question: {$userPrompt} \n\n\nPDF content: {$pdfContent}";

        return $sofliq;
    }


    public function chatCompletionFinal($finalPrompt, $userPrompt, $pdfContent)
    {
        $openAi = initOpenAi();


        $completion =  $openAi->chat();

        $completion = convertJsonDecode($completion);

        $responseContent = isset($completion["choices"][0], $completion["choices"][0]["message"], $completion["choices"][0]["message"]["content"]) ? $completion["choices"][0]["message"]["content"] : null;

        return $responseContent;
    }

    public function getPdfChatById($id)
    {

        return PdfChat::query()->with(["conversations"])->findOrFail($id);
    }

    public function getPdfChatByChatCode($chatCode)
    {

        return PdfChat::query()->with(["conversations"])->where("chat_code",$chatCode)->firstOrFail();
    }

    public function setConfigurations(array $contents )
    {
        $pdfContent  = $contents[0];
        $finalPrompt = $contents[1];
        $userPrompt  = $contents[2];

        $pdfChat = $this->getPdfChatByChatCode(getSession($this::SESSION_PDF_CHAT_CODE));

        $histories = [];

        $conversations = $pdfChat->conversations;

        $histories[] = ["role" => "system", "content" => "You are my Ai Pdf Chat Assistant" ];
        $histories[] = ["role" => "assistant", "content" => $conversations[0]->pdf_content ];




        $histories[] = [
            "role"    => "user",
            "content" => $finalPrompt
        ];

        $histories[] = [
            "role"    => "user",
            "content" => $userPrompt
        ];

        $configurations = [
            "model"             => openAiModel('chat'),
            'stream'            => true,
            'temperature'       => 1.0,
            'presence_penalty'  => 0.6,
            'frequency_penalty' => 0,
            "messages" => $histories
        ];

        Log::info("Histories :  Here : ".json_encode($configurations));

        return $configurations;
    }


    public function updateAiResponse($pdfChatConversation, $aiResponse = null)
    {
        if(empty($aiResponse)) {
            return null;
        }

        $pdfChatConversation->update([
            "ai_response" => $aiResponse
        ]);

        return $pdfChatConversation;
    }

    public function setPdfSessionChatCode($chatCode)
    {
        session()->put(["chat_code" => $chatCode]);
    }

    public function getPdfSessionChatCode()
    {
       return  session("chat_code");
    }

    public function getPdfChatConversationById($id)
    {
        return PdfChatConversation::query()->latest()->findOrFail($id);
    }

    public function updatePdfChatConversation(PdfChatConversation $pdfChatConversation, array $payloads )
    {

        $pdfChatConversation->update($payloads);

        //Log::info("After Updating Pdf Chat Conversation : ".json_encode($pdfChatConversation));

        return $pdfChatConversation;
    }


    public function updateUserBalancechatCompletion($completionTokens)
    {
        $user = user();
        $user->update([
            "balance" => $user->balance - $completionTokens
        ]);

    }

    public function getPdfChatConversationsWords(PdfChatConversation $pdfChatConversation)
    {
        if(!empty($pdfChatConversation->ai_response)){
            return count(explode(' ', $pdfChatConversation->ai_response));
        }

        return 0;
    }


    public function parseChatResponse($chatResponse = null)
    {
        $text = $output ="";
        if (!empty($chatResponse)) {
            foreach ($chatResponse as $singleData) {
                if (!empty($singleData)) {
                    $singleData = convertJsonDecode(trim($singleData));

                    if (isset($singleData["choices"][0]["delta"]["content"])) {
                        $content = $singleData["choices"][0]["delta"]["content"];
                        $text   .= $content;
                        $output .= $content;
                    }
                }
            }
        }

        return [
            "text" => $text,
            "output" => $output
        ];
    }

}
