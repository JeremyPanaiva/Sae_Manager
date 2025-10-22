<?php

namespace Views\Base;

use Models\User\User;
use Views\View;

abstract class BaseView implements View
{
    private HeaderView $header;
    private FooterView $footer;

    protected ?User $user;

    /**
     * Données dynamiques fournies aux templates (clé => valeur)
     * @var array<string,string>
     */
    protected array $data = [];

    public function __construct()
    {
        $this->user = null;
    }

    function render(): string
    {
        $this->header = new HeaderView();
        $this->footer = new FooterView();
        return $this->header->renderBody()
            . $this->renderBody()
            . $this->footer->renderBody();
    }

    function setUser(?User $user)
    {
        $this->user = $user;
    }

    /**
     * Injecte des données pour le template
     * @param array<string,string> $data
     */
    public function setData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    function renderBody(): string
    {
        $template = file_get_contents($this->templatePath());

        foreach ($this->templateKeys() as $key => $value) {
            $replacement = '';
            if (is_string($value)) {
                if (array_key_exists($value, $this->data)) {
                    $replacement = (string)$this->data[$value];
                } else {
                    $replacement = $value;
                }
            }
            $template = str_replace("{{{$key}}}", $replacement, $template);
        }

        return $template;
    }
}