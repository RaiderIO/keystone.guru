@extends('errors.error-layout', [
    'title' => __('views/errors.500.title'),
    'code' => 500,
    'message' => __('views/errors.500.message')
    ])
