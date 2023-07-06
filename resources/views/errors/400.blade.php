@extends('errors.error-layout', [
    'title' => __('views/errors.400.title'),
    'code' => 400,
    'message' => __('views/errors.400.message')
    ])