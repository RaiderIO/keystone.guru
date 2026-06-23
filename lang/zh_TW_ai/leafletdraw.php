<?php

return [

    'draw' => [
        'toolbar' => [
            'actions' => [
                'title' => '取消繪製',
                'text'  => '取消',
            ],
            'finish' => [
                'title' => '完成繪製',
                'text'  => '完成',
            ],
            'undo' => [
                'title' => '刪除最後繪製的點',
                'text'  => '刪除最後一個點',
            ],
            'buttons' => [
                'polyline'     => '繪製折線',
                'polygon'      => '繪製多邊形',
                'rectangle'    => '繪製矩形',
                'circle'       => '繪製圓形',
                'marker'       => '繪製標記',
                'circlemarker' => '繪製圓形標記',
            ],
        ],
        'handlers' => [
            'circle' => [
                'tooltip' => [
                    'start' => '點擊並拖動以繪製圓形。',
                ],
                'radius' => '半徑',
            ],
            'circlemarker' => [
                'tooltip' => [
                    'start' => '點擊地圖以放置圓形標記。',
                ],
            ],
            'marker' => [
                'tooltip' => [
                    'start' => '點擊地圖以放置標記。',
                ],
            ],
            'polygon' => [
                'tooltip' => [
                    'start' => '點擊開始繪製形狀。',
                    'cont'  => '點擊以繼續繪製形狀。',
                    'end'   => '點擊第一個點以關閉此形狀。',
                ],
            ],
            'polyline' => [
                'error'   => '<strong>錯誤:</strong> 形狀邊緣不能交叉！',
                'tooltip' => [
                    'start' => '點擊開始繪製線條。',
                    'cont'  => '點擊以繼續繪製線條。',
                    'end'   => '點擊最後一個點以完成線條。',
                ],
            ],
            'rectangle' => [
                'tooltip' => [
                    'start' => '點擊並拖動以繪製矩形。',
                ],
            ],
            'simpleshape' => [
                'tooltip' => [
                    'end' => '鬆開鼠標以完成繪製。',
                ],
            ],
            'path' => [
                'tooltip' => [
                    'start' => '點擊開始繪製路徑。',
                    'cont'  => '點擊以繼續繪製路徑。',
                    'end'   => '點擊工具欄上的“完成”按鈕以完成路徑。',
                ],
            ],
            'brushline' => [
                'tooltip' => [
                    'start' => '點擊開始繪製線條。',
                    'cont'  => '點擊並拖動以繼續繪製線條。',
                    'end'   => '繼續點擊/拖動，完成後，按工具欄上的“完成”按鈕完成線條。',
                ],
            ],
            'arrow' => [
                'tooltip' => [
                    'start' => '',
                    'cont'  => '',
                ],
            ],
        ],
    ],
    'edit' => [
        'toolbar' => [
            'actions' => [
                'save' => [
                    'title' => '保存更改',
                    'text'  => '保存',
                ],
                'cancel' => [
                    'title' => '取消編輯，放棄所有更改',
                    'text'  => '取消',
                ],
                'clearAll' => [
                    'title' => '清除所有圖層',
                    'text'  => '清除所有',
                ],
            ],
            'buttons' => [
                'edit'           => '編輯圖層',
                'editDisabled'   => '無可編輯圖層',
                'remove'         => '刪除圖層',
                'removeDisabled' => '無可刪除圖層',
            ],
        ],
        'handlers' => [
            'edit' => [
                'tooltip' => [
                    'text'    => '拖動控制手柄或標記以編輯特徵。',
                    'subtext' => '點擊取消以撤銷更改。',
                ],
            ],
            'remove' => [
                'tooltip' => [
                    'text' => '點擊一個特徵以刪除。',
                ],
            ],
        ],
    ],

];
