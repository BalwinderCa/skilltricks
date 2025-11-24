<?php

use App\Http\Services\ElevenLabsService;

trait ElevenLabsTrait
{
    protected $elevenLabService;
    public function __construct(ElevenLabsService $elevenLabService){
        $this->elevenLabService = $elevenLabService;
    }
    public function index():array
    {
        $this->elevenLabService->models();
        $data = [];
        return $data;
    }
}