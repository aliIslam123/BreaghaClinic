<?php
if (!isset($schema_page_key)) $schema_page_key = 'home';
require_once __DIR__ . '/config/seo.php';
$seo = get_page_seo($schema_page_key);
$s = $seo['site'];
$p = $seo['page'];

$breadcrumb_items = [
    [
        '@type' => 'ListItem',
        'position' => 1,
        'name' => 'الرئيسية',
        'item' => $s['url'],
    ],
];
if ($schema_page_key !== 'home') {
    $breadcrumb_items[] = [
        '@type' => 'ListItem',
        'position' => 2,
        'name' => $p['og_title'] ?? $p['title'],
        'item' => $s['url'] . $p['canonical'],
    ];
}
if ($schema_page_key === 'services') {
    $breadcrumb_items[] = [
        '@type' => 'ListItem',
        'position' => count($breadcrumb_items) + 1,
        'name' => 'تفاصيل الخدمة',
        'item' => isset($_GET['id']) ? $s['url'] . '/services?id=' . intval($_GET['id']) : $s['url'] . '/services',
    ];
}
if ($schema_page_key === 'about') {
    $breadcrumb_items[] = [
        '@type' => 'ListItem',
        'position' => count($breadcrumb_items) + 1,
        'name' => 'الدكتورة سماح جمال',
        'item' => $s['url'] . '/about#dr-samah',
    ];
}

$schema = [
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'MedicalClinic',
            '@id' => $s['url'] . '#medical-clinic',
            'name' => $s['name'],
            'alternateName' => 'عيادة براجيا',
            'description' => 'عيادة براجيا للتجميل والليزر والجلدية — خدمات طبية وتجميلية متكاملة في العبور',
            'url' => $s['url'],
            'telephone' => $s['phone'],
            'email' => $s['email'],
            'image' => $s['logo'],
            'logo' => $s['logo'],
            'priceRange' => '$$',
            'currenciesAccepted' => 'EGP',
            'paymentAccepted' => 'Cash, Credit Card',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $s['address'],
                'addressLocality' => $s['city'],
                'addressRegion' => $s['region'],
                'addressCountry' => $s['country'],
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => $s['lat'],
                'longitude' => $s['lng'],
            ],
            'hasMap' => $s['google_maps'],
            'openingHoursSpecification' => [
                [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                    'opens' => '10:00',
                    'closes' => '23:00',
                ],
            ],
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.8',
                'reviewCount' => '150',
                'bestRating' => '5',
                'worstRating' => '1',
            ],
            'medicalSpecialty' => [
                'Dermatology',
                'PlasticSurgery',
                'LaserHairRemoval',
                'Gynecology',
                'Gastroenterology',
            ],
            'availableService' => [
                [
                    '@type' => 'MedicalProcedure',
                    'name' => 'ليزر إزالة الشعر',
                    'procedureType' => 'http://schema.org/SurgicalProcedure',
                ],
                [
                    '@type' => 'MedicalProcedure',
                    'name' => 'بوتوكس',
                ],
                [
                    '@type' => 'MedicalProcedure',
                    'name' => 'فيلر',
                ],
                [
                    '@type' => 'MedicalTherapy',
                    'name' => 'عناية بالبشرة',
                ],
                [
                    '@type' => 'MedicalProcedure',
                    'name' => 'تقشير كيميائي',
                ],
                [
                    '@type' => 'MedicalProcedure',
                    'name' => 'ميزوثيرابي',
                ],
                [
                    '@type' => 'MedicalProcedure',
                    'name' => 'PRP',
                ],
            ],
            'sameAs' => [
                $s['facebook'],
                $s['instagram'],
                $s['tiktok'],
                $s['snapchat'],
                $s['google_maps'],
            ],
        ],
        [
            '@type' => 'LocalBusiness',
            '@id' => $s['url'] . '#local-business',
            'name' => $s['name'],
            'image' => $s['logo'],
            'url' => $s['url'],
            'telephone' => $s['phone'],
            'email' => $s['email'],
            'priceRange' => '$$',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $s['address'],
                'addressLocality' => $s['city'],
                'addressRegion' => $s['region'],
                'addressCountry' => $s['country'],
                'postalCode' => '',
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => $s['lat'],
                'longitude' => $s['lng'],
            ],
            'openingHoursSpecification' => [
                [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
                    'opens' => '10:00',
                    'closes' => '23:00',
                ],
            ],
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.8',
                'reviewCount' => '150',
                'bestRating' => '5',
            ],
        ],
        [
            '@type' => 'Organization',
            '@id' => $s['url'] . '#organization',
            'name' => $s['name'],
            'url' => $s['url'],
            'logo' => [
                '@type' => 'ImageObject',
                'url' => $s['logo'],
                'width' => 512,
                'height' => 512,
            ],
            'image' => $s['logo'],
            'email' => $s['email'],
            'telephone' => $s['phone'],
            'sameAs' => [
                $s['facebook'],
                $s['instagram'],
                $s['tiktok'],
                $s['snapchat'],
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => $s['phone'],
                'contactType' => 'customer service',
                'availableLanguage' => ['Arabic', 'English'],
                'areaServed' => 'EG',
            ],
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $s['address'],
                'addressLocality' => $s['city'],
                'addressRegion' => $s['region'],
                'addressCountry' => $s['country'],
            ],
        ],
        [
            '@type' => 'WebSite',
            '@id' => $s['url'] . '#website',
            'name' => $s['name'],
            'url' => $s['url'],
            'inLanguage' => 'ar',
            'publisher' => [
                '@id' => $s['url'] . '#organization',
            ],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $s['url'] . '/search?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ],
        [
            '@type' => 'Physician',
            '@id' => $s['url'] . '#physician-samah',
            'name' => 'د. سماح جمال',
            'jobTitle' => 'استشاري الجلدية والتجميل والليزر',
            'worksFor' => [
                '@id' => $s['url'] . '#medical-clinic',
            ],
            'medicalSpecialty' => [
                'Dermatology',
                'PlasticSurgery',
                'LaserHairRemoval',
            ],
            'gender' => 'Female',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $s['address'],
                'addressLocality' => $s['city'],
                'addressRegion' => $s['region'],
                'addressCountry' => $s['country'],
            ],
            'telephone' => $s['phone'],
        ],
        [
            '@type' => 'BreadcrumbList',
            '@id' => $s['url'] . '/#breadcrumb',
            'itemListElement' => $breadcrumb_items,
        ],
        [
            '@type' => 'FAQPage',
            '@id' => $s['url'] . '#faq',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => 'ما هي خدمات عيادة براجيا؟',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'توفر عيادة براجيا مجموعة متنوعة من الخدمات تشمل: ليزر إزالة الشعر، بوتوكس، فيلر، عناية بالبشرة، تقشير كيميائي، ميزوثيرابي، PRP، خدمات نساء وتوليد، وأمراض الباطنة.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'ما هي مواعيد عمل عيادة براجيا؟',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'تعمل عيادة براجيا يومياً من الساعة 10 صباحاً حتى 11 مساءً، بما في ذلك أيام الجمعة والعطل.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'أين تقع عيادة براجيا؟',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'تقع عيادة براجيا في العبور، شارع عباس العقاد، الحي التاسع، First Mall، الدور الثاني، محافظة القليوبية.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'كيف أحجز موعد في عيادة براجيا؟',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'يمكنك الحجز عبر موقعنا الإلكتروني أو التواصل معنا على الواتساب على الرقم 01108343753.',
                    ],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'هل تتوفر استشارات أولى مجانية في عيادة براجيا؟',
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => 'نعم، يمكنك التواصل معنا عبر الواتساب أو الهاتف على الرقم 01108343753 للتفاصيل حول الاستشارات المتاحة.',
                    ],
                ],
            ],
        ],
    ],
];
?>
<script type="application/ld+json">
<?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?>
</script>
