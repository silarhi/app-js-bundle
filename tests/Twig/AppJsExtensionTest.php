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

namespace Silarhi\AppJsBundle\Tests\Twig;

use DateTimeImmutable;
use DateTimeZone;
use LogicException;
use PHPUnit\Framework\TestCase;
use Silarhi\AppJsBundle\Tests\Fixtures\Enum\Kind;
use Silarhi\AppJsBundle\Tests\Fixtures\Enum\Status;
use Silarhi\AppJsBundle\Twig\AppJsExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

final class AppJsExtensionTest extends TestCase
{
    public function testRendersThePagesAndTheirParams(): void
    {
        self::assertSame(
            'data-page="[&quot;party&quot;]" data-page-params="{&quot;id&quot;:3}"',
            (new AppJsExtension())->loadPage('party', ['id' => 3]),
        );
    }

    public function testRendersNoParamsAsAnObject(): void
    {
        self::assertSame('data-page="[&quot;a&quot;,&quot;b&quot;]" data-page-params="{}"', (new AppJsExtension())->loadPage(['a', 'b']));
        self::assertStringEndsWith('data-page-params="{}"', (new AppJsExtension())->loadPage('a', []));
    }

    public function testNormalizesTheParamsJsonCannotEncode(): void
    {
        // Its parent and its children point to each other
        $form = new FormView();
        $form->vars['id'] = 'party';
        $form->children['name'] = new FormView($form);
        $form->children['name']->vars['id'] = 'party_name';

        $attributes = $this->createExtension()->loadPage('party', [
            'form' => $form,
            'field' => $form->children['name'],
            'startsAt' => new DateTimeImmutable('2026-11-26 10:30:00', new DateTimeZone('Europe/Paris')),
            'status' => Status::EInvoice,
            'kind' => Kind::Date,
            'title' => new TranslatableMessage('party.title'),
            'items' => [['status' => Status::Draft], Kind::Text],
        ]);

        $params = '{"form":"party","field":"party_name","startsAt":"2026-11-26T10:30:00+01:00","status":"e_invoice","kind":"Date","title":"PARTY.TITLE","items":[{"status":"draft"},"Text"]}';
        self::assertSame('data-page="[&quot;party&quot;]" data-page-params="' . htmlspecialchars($params, \ENT_QUOTES) . '"', $attributes);
    }

    public function testNeedsATranslatorForATranslatableMessage(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unable to translate the "Symfony\\Component\\Translation\\TranslatableMessage" parameter of load_page() without translator');

        (new AppJsExtension())->loadPage('party', ['title' => new TranslatableMessage('party.title')]);
    }

    private function createExtension(): AppJsExtension
    {
        return new AppJsExtension(new class implements TranslatorInterface {
            use TranslatorTrait;

            /**
             * @param array<mixed> $parameters
             */
            public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return strtoupper((string) $id);
            }
        });
    }
}
