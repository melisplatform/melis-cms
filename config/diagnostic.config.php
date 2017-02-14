<?php

return array(

    'plugins' => array(
        'diagnostic' => array(
            'MelisCms' => array(
                'testFolder' => 'test',
                'moduleTestName' => 'MelisCmsTest',
                'db' => array(
                    'getMelisCmsLang' => array(
                        'model' => 'MelisEngine\Model\MelisCmsLang',
                        'model_table' => 'MelisEngine\Model\Tables\MelisCmsLangTable',
                        'db_table_name' => 'melis_cms_lang',
                    ),
                    'getMelisPageLang' => array(
                        'model' => 'MelisEngine\Model\MelisPageLang',
                        'model_table' => 'MelisEngine\Model\Tables\MelisPageLangTable',
                        'db_table_name' => 'melis_cms_page_lang',
                    ),
                    'getMelisCmsPagePublished' => array(
                        'model' => 'MelisEngine\Model\MelisCalendar',
                        'model_table' => 'MelisEngine\Model\Tables\MelisPagePublished',
                        'db_table_name' => 'melis_cms_page_published',
                    ),
                    'getMelisCmsPageSaved' => array(
                        'model' => 'MelisEngine\Model\MelisPageSaved',
                        'model_table' => 'MelisEngine\Model\Tables\MelisPageSavedTable',
                        'db_table_name' => 'melis_cms_page_saved',
                    ),
                    'getMelisCmsPageSeo' => array(
                        'model' => 'MelisEngine\Model\MelisPageSeo',
                        'model_table' => 'MelisEngine\Model\Tables\MelisPageSeoTable',
                        'db_table_name' => 'melis_cms_page_seo',
                    ),
                    'getMelisCmsPageTree' => array(
                        'model' => 'MelisEngine\Model\MelisPageTree',
                        'model_table' => 'MelisEngine\Model\Tables\MelisPageTreeTable',
                        'db_table_name' => 'melis_cms_page_tree',
                    ),
                    'getMelisCmsPlatformIds' => array(
                        'model' => 'MelisEngine\Model\MelisPlatformIds',
                        'model_table' => 'MelisEngine\Model\Tables\MelisPlatformIdsTable',
                        'db_table_name' => 'melis_cms_platform_ids',
                    ),
                    'getMelisCmsSite' => array(
                        'model' => 'MelisEngine\Model\MelisSite',
                        'model_table' => 'MelisEngine\Model\Tables\MelisSiteTable',
                        'db_table_name' => 'melis_cms_site',
                    ),
                    'getMelisCmsSite301' => array(
                        'model' => 'MelisEngine\Model\MelisSite301',
                        'model_table' => 'MelisEngine\Model\Tables\MelisSite301Table',
                        'db_table_name' => 'melis_cms_site_301',
                    ),
                    'getMelisCmsSite404' => array(
                        'model' => 'MelisEngine\Model\MelisSite404',
                        'model_table' => 'MelisEngine\Model\Tables\MelisSite404Table',
                        'db_table_name' => 'melis_cms_site_404',
                    ),
                    'getMelisCmsSiteDomain' => array(
                        'model' => 'MelisEngine\Model\MelisSiteDomain',
                        'model_table' => 'MelisEngine\Model\Tables\MelisSiteDomainTable',
                        'db_table_name' => 'melis_cms_site_domain',
                    ),
                    'getMelisCmsTemplate' => array(
                        'model' => 'MelisEngine\Model\MelisTemplate',
                        'model_table' => 'MelisEngine\Model\Tables\MelisCalendarTable',
                        'db_table_name' => 'melis_cms_template',
                    ),
                ),
            ),
        ),
    ),


);

