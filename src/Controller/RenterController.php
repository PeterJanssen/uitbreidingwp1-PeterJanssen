<?php

namespace App\Controller;

use App\Model\RenterModel;
use InvalidArgumentException;
use PDOException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class RenterController extends AbstractController
{
    private $renterModel;

    public function __construct(RenterModel $renterModel)
    {
        $this->renterModel = $renterModel;
    }

    /**
     * @Route("/renters/{id}", methods={"GET"}, name="getRenterById")
     * @param $id
     * @return JsonResponse
     */
    public function getRenterById($id)
    {
        $statuscode = 200;
        $renter = null;

        try {
            $renter = $this->renterModel->getRenterById($id);
            if (!$renter) {
                throw new NotFoundHttpException("No renter was found for id = $id.");
            }
        } catch (NotFoundHttpException $exception) {
            $statuscode = 404;
            return new JsonResponse($exception->getMessage(), $statuscode);
        } catch (PDOException $exception) {
            $statuscode = 500;
            return new JsonResponse($exception->getMessage(), $statuscode);
        }

        return new JsonResponse($renter, $statuscode);
    }

    /**
     * @Route("/renters", methods={"GET"}, name="getRentersBetweenTwoDates")
     * @param Request $request
     * @return JsonResponse
     */
    public function getRentersBetweenTwoDates(Request $request)
    {
        $statusCode = 200;

        try {
            $fromDate = $request->query->get('fromDate');
            $toDate = $request->query->get('toDate');

            if ($fromDate && $toDate) {
                $renters = $this->renterModel->getRentersBetweenTwoDates($fromDate, $toDate);
            } else {
                $renters = $this->renterModel->getRenters();
            }

            if (count($renters) === 0) {
                $statusCode = 404;
            }

        } catch (InvalidArgumentException $exception) {
            $statusCode = 400;
            return new JsonResponse($exception->getMessage(), $statusCode);
        } catch (PDOException $exception) {
            $statusCode = 500;
            return new JsonResponse($exception->getMessage(), $statusCode);
        }
        return new JsonResponse($renters, $statusCode);
    }

    /**
     * @Route("/renters", methods={"POST"}, name="createRenter")
     * @param Request $request
     * @return JsonResponse
     */
    public function createRenter(Request $request)
    {
        $statusCode = 201;
        $data = json_decode($request->getContent(), true);

        try {
            if (!$data) {
                throw new BadRequestHttpException();
            }

            $this->renterModel->createRenter($data);
        } catch (InvalidArgumentException | BadRequestHttpException $exception) {
            $statusCode = 400;
            return new JsonResponse($exception->getMessage(), $statusCode);
        } catch (PDOException $exception) {
            $statusCode = 500;
            return new JsonResponse($exception->getMessage(), $statusCode);
        }

        return new JsonResponse(null, $statusCode);
    }

    /**
     * @Route("/renters/{id}", methods={"PATCH", "OPTIONS"}, name="updateRenterRentedRoom")
     * @param $id
     * @param Request $request
     * @return JsonResponse
     */
    public function updateRenterRentedRoom($id, Request $request)
    {
        $statuscode = 204;

        $data = json_decode($request->getContent(), true);

        try {
            if (!$data) {
                throw new BadRequestHttpException();
            }

            $this->renterModel->updateRenterRentedRoom($id, $data);
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

        return new JsonResponse(null, $statuscode);
    }
}