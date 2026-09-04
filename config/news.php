<?php

return [
    'brand_name' => env('NEWS_BRAND_NAME', 'iHowz'),
    'brand_voice' => env('NEWS_BRAND_VOICE', 'professional, clear, practical guidance for landlords and letting agents'),
    'default_author' => env('NEWS_DEFAULT_AUTHOR', 'iHowz Editorial'),
    'keywords' => env('NEWS_KEYWORDS', 'Private Rental Sector,Landlord Law,HMO Regulations,PRS,Section 21,Section 8,Buy to Let,Tenant Rights,Rent Controls'),
    'rss_feeds' => array_filter(explode(',', env('NEWS_RSS_FEEDS', ''))),
    'default_llm_model' => env('NEWS_DEFAULT_LLM_MODEL', 'gpt-4.1'),
    'default_image_model' => env('NEWS_DEFAULT_IMAGE_MODEL', 'gpt-image-1'),
];
