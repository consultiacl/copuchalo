<?php
/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 */
namespace Facebook\InstantArticles\Transformer\Rules;

use Facebook\InstantArticles\Elements\SocialEmbed;
use Facebook\InstantArticles\Elements\Caption;
use Facebook\InstantArticles\Elements\InstantArticle;
use Facebook\InstantArticles\Transformer\Warnings\InvalidSelector;

class SocialEmbedRule extends ConfigurationSelectorRule
{
    const PROPERTY_IFRAME = 'socialembed.iframe';
    const PROPERTY_URL = 'socialembed.url';

    public function getContextClass()
    {
        return InstantArticle::getClassName();
    }

    public static function create()
    {
        return new SocialEmbedRule();
    }

    public static function createFrom($configuration)
    {
        $social_embed_rule = self::create();
        $social_embed_rule->withSelector($configuration['selector']);

        $social_embed_rule->withProperties(
            array(
                self::PROPERTY_IFRAME,
                self::PROPERTY_URL
            ),
            $configuration
        );

        return $social_embed_rule;
    }

    public function apply($transformer, $instant_article, $node)
    {
        $social_embed = SocialEmbed::create();

        // Builds the image
        $iframe = $this->getProperty(self::PROPERTY_IFRAME, $node);
        $url = $this->getProperty(self::PROPERTY_URL, $node);
        if ($iframe) {
            $social_embed->withHTML($iframe);
        }
        if ($url) {
            $social_embed->withSource($url);
        }
        if ($iframe || $url) {
            $instant_article->addChild($social_embed);
        } else {
            $transformer->addWarning(
                new InvalidSelector(
                    'iframe and/or url',
                    $instant_article,
                    $node,
                    $this
                )
            );
        }

        $suppress_warnings = $transformer->suppress_warnings;
        $transformer->suppress_warnings = true;
        $transformer->transform($social_embed, $node);
        $transformer->suppress_warnings = $suppress_warnings;

        return $instant_article;
    }
}
