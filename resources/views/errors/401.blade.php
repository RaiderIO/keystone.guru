@extends('errors.error-layout', [
    'title' => __('views/errors.401.title'),
    'code' => 401,
    'message' => __('views/errors.401.message')
    ])