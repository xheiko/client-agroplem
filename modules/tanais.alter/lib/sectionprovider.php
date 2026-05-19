<?php

namespace Tanais\Alter;

use Bitrix\Intranet\CustomSection\Provider;
use Bitrix\Intranet\CustomSection\Provider\Component;
use Bitrix\Main\Web\Uri;

class SectionProvider extends Provider
{
    public function isAvailable(string $pageSettings, int $userId): bool
    {
        return true;
    }

    public function resolveComponent(string $pageSettings, Uri $url): ?Component
    {

        if ($pageSettings == 'report_list_analytics') {
            return (new Component())
                ->setComponentTemplate('')
                ->setComponentName('tanais.alter:report.list.analytics')
                ->setComponentParams([]);
        }

        if ($pageSettings == 'visit_calendar') {

            return (new Component())
                ->setComponentTemplate('')
                ->setComponentName('tanais.alter:report.visit.calendar')
                ->setComponentParams([]);
        }

    }
}
