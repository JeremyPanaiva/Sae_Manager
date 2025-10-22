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
        $template = file_get_contents($this->templatePath());

        foreach ($this->templateKeys() as $key => $value) {
            // Valeur attendue par templateKeys():
            // - souvent une valeur littérale (ex: URL ou texte) => on l'utilise directement
            // - parfois le nom d'une clé de données (ex: 'ERROR_MESSAGE') => on remplace par la valeur fournie via setData()
            $replacement = '';
            if (is_string($value)) {
                if (array_key_exists($value, $this->data)) {
                    // priorité à la donnée fournie dynamiquement
                    $replacement = (string)$this->data[$value];
                } else {
                    // sinon on utilise la valeur littérale fournie par templateKeys()
                    $replacement = $value;
                }
            }

            $template = str_replace("{{{$key}}}", $replacement, $template);
        }

        return $template;
    }
}