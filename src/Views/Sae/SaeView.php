<?php

namespace Views\Sae;

use Views\Base\BaseView;

/**
 * SAE View
 *
 * @package Views\Sae
 */
class SaeView extends BaseView
{
    /**
     * Template data
     *
     * @var array<string, mixed>
     */
    protected array $data;

    /**
     * Constructor
     *
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct();
        $this->data = $data;
    }

    /**
     * Returns template path
     *
     * @return string
     */
    public function templatePath(): string
    {
        return __DIR__ .  '/sae.php';
    }
}
