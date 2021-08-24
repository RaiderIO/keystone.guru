@extends('errors.error-layout', [
    'title' => __('views/errors.403.title'),
    'code' => 403,
    'message' => $exception->getMessage() ?: __('views/errors.403.message')
    ])
