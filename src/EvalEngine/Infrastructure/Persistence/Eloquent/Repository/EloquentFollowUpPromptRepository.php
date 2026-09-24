<?php

namespace Src\EvalEngine\Infrastructure\Persistence\Eloquent\Repository;

use Src\EvalEngine\Domain\FollowUp\FollowUpPromptRepository;
use Src\EvalEngine\Infrastructure\Persistence\Eloquent\Model\FollowUpPromptTemplateModel;

final class EloquentFollowUpPromptRepository implements FollowUpPromptRepository
{
    public function selectForDomain(?string $domain): ?string
    {
        if ($domain !== null) {
            $id = FollowUpPromptTemplateModel::where('domain', $domain)->inRandomOrder()->value('id');
            if ($id !== null) {
                return $id;
            }
        }

        return FollowUpPromptTemplateModel::inRandomOrder()->value('id');
    }

    public function findTextById(string $id): ?string
    {
        return FollowUpPromptTemplateModel::find($id)?->prompt_text;
    }
}
