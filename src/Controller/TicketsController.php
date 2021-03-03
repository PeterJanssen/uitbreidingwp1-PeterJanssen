<?php

namespace App\Controller;

use App\Model\TicketModel;
use InvalidArgumentException;
use PDOException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class TicketsController extends AbstractController
{
    private $ticketModel;

    public function __construct(TicketModel $ticketModel)
    {
        $this->ticketModel = $ticketModel;
    }

    /**
     * @Route("/tickets/", methods={"GET"}, name="getTicketsByAssetName")
     * @param Request $request
     * @return JsonResponse
     */
    public function getTicketsByAssetName(Request $request)
    {
        $statuscode = 200;
        $assetName = $request->query->get("assetName");

        try {
            if (!$assetName) {
                throw new BadRequestHttpException("No assetName query parameter set.");
            }

            $tickets = $this->ticketModel->getTicketsByAssetName($assetName);
            if (!$tickets) {
                throw new NotFoundHttpException("No tickets were found for assetName = $assetName.");
            }
        } catch (InvalidArgumentException | BadRequestHttpException $exception) {
            $statuscode = 400;
            return new JsonResponse($exception->getMessage(), $statuscode);
        } catch (NotFoundHttpException $exception) {
            $statuscode = 404;
            return new JsonResponse($exception->getMessage(), $statuscode);
        } catch (PDOException $exception) {
            $statuscode = 500;
            return new JsonResponse($exception->getMessage(), $statuscode);
        }

        return new JsonResponse($tickets, $statuscode);
    }

    /**
     * @Route("/tickets/{id}", methods={"GET"}, name="getTicketById")
     * @param $id
     * @return JsonResponse
     */
    public function getTicketById($id)
    {
        $statuscode = 200;

        try {
            $ticket = $this->ticketModel->getTicketById($id);
            if (!$ticket) {
                throw new NotFoundHttpException("No tickets were found for id = $id.");
            }
        } catch (NotFoundHttpException $exception) {
            $statuscode = 404;
            return new JsonResponse($exception->getMessage(), $statuscode);
        } catch (PDOException $exception) {
            $statuscode = 500;
            return new JsonResponse($exception->getMessage(), $statuscode);
        }

        return new JsonResponse($ticket, $statuscode);
    }

    /**
     * @Route("/tickets/", methods={"POST"}, name="createTicketForAsset")
     * @param Request $request
     * @return JsonResponse
     */
    public function createTicketForAsset(Request $request)
    {
        $statuscode = 201;
        $assetName = $request->query->get("assetName");
        $data = json_decode($request->getContent(), true);

        try {
            if (!($assetName && $data)) {
                throw new BadRequestHttpException();
            }

            $this->ticketModel->createTicketByAssetName($assetName, $data);
        } catch (InvalidArgumentException | BadRequestHttpException $exception) {
            $statuscode = 400;
            return new JsonResponse($exception->getMessage(), $statuscode);
        } catch (PDOException $exception) {
            $statuscode = 500;
            return new JsonResponse($exception->getMessage(), $statuscode);
        }

        return new JsonResponse(null, $statuscode);
    }

    /**
     * @Route("/tickets/{id}", methods={"PATCH", "OPTIONS"}, name="incrementNumberOfVotes")
     * @param $id
     * @return JsonResponse
     */
    public function incrementNumberOfVotes($id)
    {
        $statuscode = 204;

        try {
            $this->ticketModel->incrementNumberOfVotes($id);
        } catch (InvalidArgumentException $exception) {
            $statuscode = 400;
            return new JsonResponse($exception->getMessage(), $statuscode);
        } catch (NotFoundHttpException $exception) {
            $statuscode = 404;
            return new JsonResponse($exception->getMessage(), $statuscode);
        } catch (PDOException $exception) {
            $statuscode = 500;
            return new JsonResponse($exception->getMessage(), $statuscode);
        }

        return new JsonResponse(null, $statuscode);
    }
}
