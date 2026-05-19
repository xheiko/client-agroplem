<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>

<div class="reports-list-wrap">
    <div class="reports-list" id="reports_list_table_crm_activity">
        <div class="reports-list-left-corner"></div>
        <div class="reports-list-right-corner"></div>

        <style>
            .reports-list-table th:hover {
                cursor: default;
            }
        </style>

        <?php
        foreach ($arResult['REPORTS'] as $key => $sections) { ?>
            <div class="report-entity-title report-entity-title-blue"><?= $key ?></div>
            <table cellspacing="0" class="reports-list-table">
                <tbody>
                <tr>
                    <th class="reports-first-column reports-head-cell-top">
                        <div class="reports-head-cell">
                            <span class="reports-head-cell-title">Название отчета</span>
                        </div>
                    </th>
                    <th class="reports-last-column reports-head-cell-top">
                        <div class="reports-head-cell">
                            <span class="reports-head-cell-title">Дата изменения</span>
                        </div>
                    </th>
                    <th class="reports-last-column reports-head-cell-top">
                        <div class="reports-head-cell">
                            <span class="reports-head-cell-title">Запросов в неделю</span>
                        </div>
                    </th>
                    <th class="reports-last-column reports-head-cell-top">
                        <div class="reports-head-cell">
                            <span class="reports-head-cell-title">Время открытия</span>
                        </div>
                    </th>
                    <? if (\Bitrix\Main\Engine\CurrentUser::get()->isAdmin()) { ?>
                        <th class="reports-last-column reports-head-cell-top">
                            <div class="reports-head-cell">
                                <span class="reports-head-cell-title">Сотрудники</span>
                            </div>
                        </th>
                    <? } ?>
                </tr>
                <?php foreach ($sections as $section) { ?>
                    <tr class="reports-list-item">
                        <td class="reports-first-column">
                            <a title="" href="<?= $section['URL'] ?>" class="reports-title-link">
                                <?= $section['NAME'] ?>
                                <?php if ($section['DESCRIPTION']): ?>
                                    <span class="report-description"><?= $section['DESCRIPTION'] ?></span>
                                <?php endif ?>
                            </a>
                        </td>
                        <td class="reports-last-column">
                            <?= $section['DATE_MODIFIED']; ?>
                        </td>
                        <td class="reports-last-column">
                            <?= $section['COUNT']; ?>
                        </td>
                        <td class="reports-last-column">
                            <?= $section['AVERAGE_TIME']; ?>
                        </td>
                        <? if (\Bitrix\Main\Engine\CurrentUser::get()->isAdmin()) { ?>
                            <td class="reports-last-column">
                                <?= $section['USERS']; ?>
                            </td>
                        <? } ?>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        <? } ?>
    </div>
</div>