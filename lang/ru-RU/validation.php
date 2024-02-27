<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'             => ':attribute должен быть принят',
    'active_url'           => ':attribute  недопустимым URL.',
    'after'                => ':attribute должен быть датой после :date.',
    'after_or_equal'       => ':attribute должен быть датой после или равной :date.',
    'alpha'                => ':attribute может содержать только буквы.',
    'alpha_dash'           => ':attribute может содержать только буквы, цифры и тире.',
    'alpha_num'            => ':attribute может содержать только буквы и цифры.',
    'array'                => ':attribute должен быть массивом.',
    'before'               => ':attribute должен быть датой после :date.',
    'before_or_equal'      => ':attribute должен быть датой до или равной :date.',
    'between'              => [
        'numeric' => ':attribute должно быть между :min и :max.',
        'file'    => ':attribute должно быть между :min и :max килобайт.',
        'string'  => ':attribute должно быть между :min и :max символов',
        'array'   => ':attribute должно быть между :min и :max  предметов',
    ],
    'boolean'              => ':attribute поле должно быть истинным или ложным.',
    'confirmed'            => ':attribute подтверждение не совпадает.',
    'date'                 => ':attribute не действительная дата.',
    'date_format'          => ':attribute не соответствует формату :format.',
    'different'            => ':attribute и :other должны быть другими',
    'digits'               => ':attribute должен иметь :digits цифры.',
    'digits_between'       => ':attribute должно быть между :min и :max цифры.',
    'dimensions'           => ':attribute имеет недопустимые размеры изображения.',
    'distinct'             => ':attribute поле имеет повторяющееся значение.',
    'email'                => ':attribute Адрес эл. почты должен быть действительным.',
    'exists'               => 'Выбранный :attribute недействителен.',
    'file'                 => ':attribute должен быть файлом.',
    'filled'               => 'Поле :attribute, обязательное для заполнения.',
    'image'                => ':attribute должно быть изображением.',
    'in'                   => 'Выбранный :attribute недействителен',
    'in_array'             => 'Поле :attribute не существует в :other.',
    'integer'              => ':attribute  должно быть целым числом.',
    'ip'                   => ':attribute должен быть действующий IP-адрес',
    'json'                 => ':attribute должен быть действительной строкой JSON',
    'max'                  => [
        'numeric' => ':attribute не может быть больше чем :max.',
        'file'    => ':attribute не может быть больше чем :max килобайт',
        'string'  => ':attribute не может быть больше чем :max символов',
        'array'   => ':attribute не может быть больше :max предметов',
    ],
    'mimes'                => ':attribute должен быть файл типа: :values.',
    'mimetypes'            => ':attribute должен быть файл типа: :values.',
    'min'                  => [
        'numeric' => ':attribute должен быть не менее :min.',
        'file'    => ':attribute должен быть не менее :min килобайт.',
        'string'  => ':attribute должен быть не менее :min символов.',
        'array'   => ':attribute должен иметь как минимум :min предметов.',
    ],
    'not_in'               => 'Выбранный :attribute недействителен.',
    'numeric'              => ':attribute должен быть числом.',
    'present'              => 'Поле :attribute должно присутствовать.',
    'regex'                => 'Неверный формат :attribute',
    'required'             => 'Поле :attribute, обязательное для заполнения.',
    'required_if'          => 'Поле :attribute обязательно, когда :other является :value.',
    'required_unless'      => 'Поле :attribute является обязательным, если только :other не указано в :values.',
    'required_with'        => 'Поле :attribute  обязательно, когда присутствует в :values.',
    'required_with_all'    => 'Поле :attribute  обязательно, когда присутствует в :values.',
    'required_without'     => 'Поле :attribute  обязательно, когда не присутствует в :values.',
    'required_without_all' => 'Поле :attribute является обязательным, если ни один из :values присутствуют.',
    'same'                 => ':attribute и :other должны совпадать.',
    'size'                 => [
        'numeric' => ':attribute должен быть :size.',
        'file'    => ':attribute должен быть :size килобайт.',
        'string'  => ':attribute должен быть :size символов.',
        'array'   => ':attribute должен содержать :size предметов',
    ],
    'string'               => ':attribute должен быть строкой.',
    'timezone'             => ':attribute должна быть действующая зона.',
    'unique'               => ':attribute уже использовано.',
    'uploaded'             => ':attribute не удалось загрузить.',
    'url'                  => 'Формат :attribute недействителен.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'Сообщение',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [],

];
