<?php

namespace App\Controller;

use App\Entity\History;
use App\Repository\HistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\View\View;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;

class ExchangeController extends AbstractFOSRestController
{
    public function __construct(
        private readonly HistoryRepository $historyRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Rest\Post('/exchange/values', name: 'app_create_exchange')]
    #[Rest\RequestParam(
        name: 'first',
        requirements: [
            new Assert\Type(['type' => 'integer']),
            new Assert\NotBlank(),
        ],
        strict: true,
        nullable: false)]
    #[Rest\RequestParam(
        name: 'second',
        requirements: [
            new Assert\Type(['type' => 'integer']),
            new Assert\NotBlank(),
        ],
        strict: true,
        nullable: false)]
    public function createValues(int $first, int $second): View
    {
        $history = new History();
        $history->setFirstIn($first);
        $history->setSecondIn($second);
        $history->setCreatedAt(new \DateTime());

        $this->historyRepository->save($history, true);

        $this->swapValuesWithoutExtraVariable($first, $second);

        $history->setFirstOut($first);
        $history->setSecondOut($second);
        $history->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return View::create('', Response::HTTP_NO_CONTENT);
    }

    #[Rest\Get('/history', name: 'app_show_history')]
    public function showHistory(): View
    {
        return View::create(
            $this->historyRepository->findAll(),
            Response::HTTP_OK);
    }

    #[Rest\Post('/history', name: 'app_show_by_params_history')]
    #[Rest\QueryParam(
        name: 'page',
        requirements: new Assert\Type(['type' => 'string']),
        default: 1,
        strict: true,
        nullable: false)]
    #[Rest\QueryParam(
        name: 'limit',
        requirements: new Assert\Type(['type' => 'string']),
        strict: true,
        nullable: false)]
    #[Rest\QueryParam(
        name: 'sortBy',
        requirements: new Assert\Type(['type' => 'string']),
        strict: true,
        nullable: false)]
    #[Rest\QueryParam(
        name: 'sortOrder',
        requirements: new Assert\Choice(choices: ['asc', 'desc']),
        default: 'asc',
        allowBlank: false)]
    public function showHistoryByParams(
        string $page,
        string $limit,
        string $sortBy,
        string $sortOrder
    ): View {
        return View::create(
            $this->historyRepository->sortAndPaginateBy(
                $page,
                $limit,
                $sortBy,
                $sortOrder
            ) ,
            Response::HTTP_OK);
    }

    private function swapValuesWithoutExtraVariable(&$a, &$b): void
    {
        [$a, $b] = [$b, $a];
    }
}
