<?php

namespace PiShift\Content\Fields;

/**
 * An icon name from the site's icon set (rendered by the project's own views).
 */
class Icon extends Select
{
    public function type(): string
    {
        return 'icon';
    }

    protected function valueRules(): array
    {
        return $this->options === [] ? ['string', 'max:64'] : parent::valueRules();
    }
}
