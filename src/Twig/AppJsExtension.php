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

namespace Silarhi\AppJsBundle\Twig;

use BackedEnum;
use DateTimeInterface;

use function is_array;

use LogicException;
use Override;

use function sprintf;

use stdClass;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use UnitEnum;

/**
 * @internal
 */
final class AppJsExtension extends AbstractExtension
{
    public function __construct(private readonly ?TranslatorInterface $translator = null)
    {
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('load_page', $this->loadPage(...), ['is_safe' => ['html_attr']]),
        ];
    }

    /**
     * The attributes read by the `data-page` listener of @silarhi/app-js:
     *
     *     <div {{ load_page('party', {id: party.id, form: form}) }}></div>
     *
     * A form view is passed as its id, for formView(), a date in ISO 8601, an enum case as its value (its name for a
     * unit enum), a translatable message translated.
     *
     * @param string|list<string>       $pageIds
     * @param array<string, mixed>|null $params
     */
    public function loadPage(string|array $pageIds, ?array $params = null): string
    {
        $page = json_encode((array) $pageIds, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);
        $pageParams = json_encode(null === $params ? new stdClass() : $this->normalizeParams($params), \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);

        return sprintf(
            'data-page="%s" data-page-params="%s"',
            htmlspecialchars($page, \ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($pageParams, \ENT_QUOTES, 'UTF-8'),
        );
    }

    /**
     * @param array<array-key, mixed> $params
     *
     * @return array<array-key, mixed>|stdClass an empty map stays an object
     */
    private function normalizeParams(array $params): array|stdClass
    {
        if ([] === $params) {
            return new stdClass();
        }

        return array_map($this->normalizeParam(...), $params);
    }

    private function normalizeParam(mixed $value): mixed
    {
        return match (true) {
            // A form view holds its parent and children: its id is what formView() reads
            $value instanceof FormView => $value->vars['id'] ?? null,
            $value instanceof DateTimeInterface => $value->format(DateTimeInterface::ATOM),
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,
            $value instanceof TranslatableInterface => $value->trans($this->translator ?? throw new LogicException(sprintf('Unable to translate the "%s" parameter of load_page() without translator: install symfony/translation.', get_debug_type($value)))),
            is_array($value) => array_map($this->normalizeParam(...), $value),
            default => $value,
        };
    }
}
