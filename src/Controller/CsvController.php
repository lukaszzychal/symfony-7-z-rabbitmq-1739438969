<?php
declare(strict_types=1);

namespace App\Controller;

use App\Message\ImportCsvCustomers;
use App\Repository\ImportReportRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class CsvController extends AbstractController
{

    #[Route('/csv', name: 'app_csv', methods: ['GET'])]
    public function index(): Response
    {

        return $this->render('csv/index.html.twig');
    }

    #[Route('/csv', name: 'app_csv_import', methods: ['POST'])]
    public function import(Request $request, MessageBusInterface $bus): Response
    {
        /** @var UploadedFile|null $uploadedFile */
        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile || $uploadedFile->getClientOriginalExtension() !== 'csv') {
            return new JsonResponse(['error' => 'Invalid file format'], Response::HTTP_BAD_REQUEST);
        }
        $file = $uploadedFile->move(sys_get_temp_dir(), uniqid('import_', true) . '.csv');
        $bus->dispatch(new ImportCsvCustomers($file->getPathname(),$file->getFilename()));

        return $this->render('csv/process.html.twig', [
           'fileName' => $file->getFilename()
        ]);

    }


    #[Route('/import/status/{fileName}', name: 'app_import_status', methods: ['GET'])]
    public function importStatus(string $fileName, ImportReportRepository $reportRepository): JsonResponse
    {
        $report =  $reportRepository->findOneByFile($fileName);
        $percentage = $report?->getPercentage() ?? 0;
        if($report && $report->isDone()) {
            $reportRepository->remove($report);
            $reportRepository->flush();
        }
        return new JsonResponse([
            'percentage' => $percentage,
             'count_all_process' => $report?->getSizeProgress() ?? 0,
             'count_errors' => count($report?->getErrors() ?? []),
            'errors' => $report?->getErrors() ?? []
        ]);
    }

}
