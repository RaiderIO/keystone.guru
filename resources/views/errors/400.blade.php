@extends('errors.error-layout', [
    'title' => __('400 Bad request'),
    'code' => 400,
    'message' => __('Your browser sent an invalid request, please try again.')
    ])