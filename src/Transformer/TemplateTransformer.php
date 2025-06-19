<?php 
namespace App\Transformer;

use App\Entity\Template;

class TemplateTransformer {

    public function transform(Template $template): array {
        return [
            'uuid'      => $template->getUuid()?->toString(),
            'data'      => $template->getData(),
            'info'      => $template->getInfo(),
            'name'      => $template->getName(),
            'createdAt' => $template->getCreatedAt()?->format('Y-m-d H:i:s.u'),
            'updatedAt' => $template->getUpdatedAt()?->format('Y-m-d H:i:s.u'),
        ];
    }

    /**
     * @param array<int,Template> $templates
     */
    public function transformPaginate(array $templates): array {
        $templates['data'] ??= [];

        foreach ($templates['data'] as $template)
            $results[] = array_merge(
                $this->transform($template),
                [ 'log' => $template->logs ]
            );

        $templates['data'] = $results ?? [];

        return $templates;
    }
}