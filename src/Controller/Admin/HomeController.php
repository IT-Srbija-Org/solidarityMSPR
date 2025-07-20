<?php

namespace App\Controller\Admin;

use App\Entity\Transaction;
use App\Repository\DamagedEducatorRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserDonorRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin', name: 'admin_')]
final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(EntityManagerInterface $entityManager, UserDonorRepository $userDonorRepository, UserRepository $userRepository, TransactionRepository $transactionRepository, DamagedEducatorRepository $damagedEducatorRepository): Response
    {
        $totalDonors = $userDonorRepository->getTotal();
        $totalActiveDonors = $transactionRepository->getTotalActiveDonors(false);
        $totalMonthlyDonors = $userDonorRepository->getTotalMonthly();
        $totalNonMonthlyDonors = $userDonorRepository->getTotalNonMonthly();
        $sumAmountMonthlyDonors = $userDonorRepository->sumAmountMonthlyDonors();
        $sumAmountNonMonthlyDonors = $userDonorRepository->sumAmountNonMonthlyDonors();
        $totalDelegates = $userRepository->getTotalDelegates();
        $totalAdmins = $userRepository->getTotalAdmins();

        $periodItems = [];

        $sumAmountDamagedEducators = $damagedEducatorRepository->getSumAmount(false);
        $sumAmountNewTransactions = $transactionRepository->getSumAmountTransactions([Transaction::STATUS_NEW]);
        $sumAmountWaitingConfirmationTransactions = $transactionRepository->getSumAmountTransactions([Transaction::STATUS_WAITING_CONFIRMATION, Transaction::STATUS_EXPIRED]);
        $sumAmountConfirmedTransactions = $transactionRepository->getSumAmountTransactions([Transaction::STATUS_CONFIRMED]);
        $totalDamagedEducators = $damagedEducatorRepository->getTotals(false);
        $averageAmountPerDamagedEducator = 0;
        if ($sumAmountConfirmedTransactions > 0 && $totalDamagedEducators > 0) {
            $averageAmountPerDamagedEducator = floor($sumAmountConfirmedTransactions / $totalDamagedEducators);
        }
        $periodItems[] = [
            'totalDamagedEducators' => $totalDamagedEducators,
            'sumAmountDamagedEducators' => $sumAmountDamagedEducators,
            'sumAmountNewTransactions' => $sumAmountNewTransactions,
            'sumAmountWaitingConfirmationTransactions' => $sumAmountWaitingConfirmationTransactions,
            'sumAmountConfirmedTransactions' => $sumAmountConfirmedTransactions,
            'averageAmountPerDamagedEducator' => $averageAmountPerDamagedEducator,
        ];

        return $this->render('admin/home/index.html.twig', [
            'totalDonors' => $totalDonors,
            'totalActiveDonors' => $totalActiveDonors,
            'totalMonthlyDonors' => $totalMonthlyDonors,
            'totalNonMonthlyDonors' => $totalNonMonthlyDonors,
            'sumAmountMonthlyDonors' => $sumAmountMonthlyDonors,
            'sumAmountNonMonthlyDonors' => $sumAmountNonMonthlyDonors,
            'totalDelegate' => $totalDelegates,
            'totalAdmins' => $totalAdmins,
            'periodItems' => $periodItems,
        ]);
    }
}
