@extends('errors.error-layout', [
    'title' => __('views/errors.404.title'),
    'code' => 404,
    'message' => __('views/errors.404.message')
    ])