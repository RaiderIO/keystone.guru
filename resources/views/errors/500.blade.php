@extends('errors.error-layout', [
    'title' => __('500 Internal Server Error'),
    'code' => 500,
    'message' => __('Whoops, something went wrong on our servers.')
    ])
