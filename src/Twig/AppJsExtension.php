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

use Override;

use function sprintf;

use stdClass;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @internal
 */
final class AppJsExtension extends AbstractExtension
{
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
     *     <div {{ load_page('party', {id: party.id}) }}></div>
     *
     * @param string|list<string>       $pageIds
     * @param array<string, mixed>|null $params
     */
    public function loadPage(string|array $pageIds, ?array $params = null): string
    {
        $page = json_encode((array) $pageIds, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);
        $pageParams = json_encode($params ?? new stdClass(), \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);

        return sprintf(
            'data-page="%s" data-page-params="%s"',
            htmlspecialchars($page, \ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($pageParams, \ENT_QUOTES, 'UTF-8'),
        );
    }
}
