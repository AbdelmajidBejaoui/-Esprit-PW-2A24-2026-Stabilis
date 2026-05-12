<?php
require_once __DIR__ . '/../../../Controllers/UserC.php';
require_once __DIR__ . '/../../../Services/UserPdfExporter.php';

$userC = new UserC();
$users = $userC->getAllUsersForExport();

$exporter = new PdfExporter();
$pdfContent = $exporter->generateUsersReport($users);

if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="utilisateurs_' . date('Y-m-d_His') . '.pdf"');
header('Content-Length: ' . strlen($pdfContent));

echo $pdfContent;
exit;
