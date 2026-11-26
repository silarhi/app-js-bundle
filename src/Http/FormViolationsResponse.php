<?php

declare(strict_types=1);

/*
 * This file is part of the App JS Bundle package.
 *
 * (c) SILARHI <dev@silarhi.fr>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Silarhi\AppJsBundle\Http;

use Silarhi\AppJsBundle\Form\FormViolations;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The errors of a submitted form as JSON, a 422 by default, for postForm() of @silarhi/app-js/http:
 *
 *     {"violations": [{"path": "phones.0.number", "message": "This value is not valid."}]}
 */
final class FormViolationsResponse extends JsonResponse
{
    /**
     * @param FormInterface<mixed>               $form
     * @param array<string, list<string>|string> $headers
     */
    public function __construct(FormInterface $form, int $status = Response::HTTP_UNPROCESSABLE_ENTITY, array $headers = [])
    {
        parent::__construct(['violations' => FormViolations::collect($form)], $status, $headers);
    }
}
