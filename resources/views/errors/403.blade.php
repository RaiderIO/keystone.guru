@extends('errors.error-layout', [
    'title' => __('view_errors.403.title'),
    'code' => 403,
    'message' => $exception->getMessage() ?: __('view_errors.403.message')
    ])
