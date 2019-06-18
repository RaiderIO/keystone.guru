@extends('errors.error-layout', [
    'title' => __('403 Forbidden'),
    'code' => 403,
    'message' => __($exception->getMessage() ?: 'Sorry, you are forbidden from accessing this page.')
    ])
