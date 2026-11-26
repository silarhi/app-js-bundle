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

namespace Silarhi\AppJsBundle\Tests\Form;

use PHPUnit\Framework\TestCase;
use Silarhi\AppJsBundle\Form\FormViolations;
use Silarhi\AppJsBundle\Http\FormViolationsResponse;
use Silarhi\AppJsBundle\Tests\Fixtures\Form\UserType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;

final class FormViolationsTest extends TestCase
{
    public function testKeysTheErrorsByTheirPathInRenderingOrder(): void
    {
        $form = $this->createUserForm();
        $form->addError(new FormError('The user is incomplete.'));
        $form->get('address')->get('city')->addError(new FormError('This value should not be blank.'));
        $form->get('phones')->get('1')->get('number')->addError(new FormError('This value is not a phone number.'));
        $form->get('name')->addError(new FormError('This value is too short.'));
        $form->get('name')->addError(new FormError('This value is not valid.'));

        self::assertSame([
            ['path' => '', 'message' => 'The user is incomplete.'],
            ['path' => 'name', 'message' => 'This value is too short.'],
            ['path' => 'name', 'message' => 'This value is not valid.'],
            ['path' => 'address.city', 'message' => 'This value should not be blank.'],
            ['path' => 'phones.1.number', 'message' => 'This value is not a phone number.'],
        ], FormViolations::collect($form));
    }

    public function testHasNoViolationWithoutError(): void
    {
        self::assertSame([], FormViolations::collect($this->createUserForm()));
    }

    public function testAnswersTheViolationsAsAnUnprocessableJson(): void
    {
        $form = $this->createUserForm();
        $form->get('phones')->get('0')->get('number')->addError(new FormError('Invalid "number".'));

        $response = new FormViolationsResponse($form);

        self::assertSame(422, $response->getStatusCode());
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertSame('{"violations":[{"path":"phones.0.number","message":"Invalid \u0022number\u0022."}]}', $response->getContent());
        self::assertSame(400, (new FormViolationsResponse($form, 400))->getStatusCode());
    }

    /**
     * @return FormInterface<mixed>
     */
    private function createUserForm(): FormInterface
    {
        $formFactory = Forms::createFormFactory();
        self::assertInstanceOf(FormFactoryInterface::class, $formFactory);

        return $formFactory->create(UserType::class, ['phones' => [['number' => '01'], ['number' => '02']]]);
    }
}
