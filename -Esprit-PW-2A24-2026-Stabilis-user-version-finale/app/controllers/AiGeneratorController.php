<?php
require_once __DIR__ . '/../models/Defi.php';

class AiGeneratorController
{
    private Defi $defi;

    public function __construct(mysqli $db)
    {
        $this->defi = new Defi($db);
    }

    public function index(): void
    {
        $recentDefis = array_slice($this->defi->getAll(), 0, 10);
        require __DIR__ . '/../views/ai-generator/index.php';
    }
}
