@extends('errors.error-layout', [
    'title' => __('401 Unauthorized'),
    'code' => 401,
    'message' => __('Sorry, you are not authorized to access this page.')
    ])