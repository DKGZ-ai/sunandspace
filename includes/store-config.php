<?php

function ss_support_email(): string
{
    return 'sunspace.trading@gmail.com';
}

function ss_support_phone(): string
{
    return '09648580564';
}

function ss_support_phone_tel(): string
{
    return 'tel:' . preg_replace('/\s+/', '', ss_support_phone());
}

function ss_facebook_url(): string
{
    return 'https://www.facebook.com/people/Xinyao-Solar-Power-PH/61576476432080/';
}

function ss_delivery_window(): string
{
    return '3–7 business days';
}

function ss_return_window_days(): int
{
    return 7;
}

function ss_return_window_label(): string
{
    return ss_return_window_days() . ' days';
}

function ss_refund_processing_days(): string
{
    return '5–7 business days';
}
