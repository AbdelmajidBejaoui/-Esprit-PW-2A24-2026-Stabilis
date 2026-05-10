<?php

class AiWeeklyStoryController
{
    public function __construct(mysqli $db)
    {
    }

    public function index(): void
    {
        require __DIR__ . '/../views/ai-weekly-story/index.php';
    }
}
