<?php
namespace Views;
abstract class AbstractView implements View
{
    /**
     * Données dynamiques fournies aux templates (clé => valeur)
     * Ex : ['token' => 'abc', 'email' => 'a@b.c']
     * Les clés correspondant aux valeurs retournées par templateKeys() seront remplacées.
     *
     * @var array<string,string>
     */
    protected array $data = [];

    /**
     * Injecte des données pour le template
     *
     * @param array<string,string> $data
     */
    public function setData(array $data): void
    {
        $this->data = array_merge($this->data, $data);
    }

    function renderBody(): string
    {
        $templatePath = $this->templatePath();

        ob_start();
        extract($this->data);
        include $templatePath;
        return ob_get_clean();
    }
}