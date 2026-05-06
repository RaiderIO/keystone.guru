<?php

return [

    'draw' => [
        'toolbar' => [
            'actions' => [
                'title' => '取消绘图',
                'text'  => '取消',
            ],
            'finish' => [
                'title' => '完成绘图',
                'text'  => '完成',
            ],
            'undo' => [
                'title' => '删除最后绘制的点',
                'text'  => '删除最后一个点',
            ],
            'buttons' => [
                'polyline'     => '绘制折线',
                'polygon'      => '绘制多边形',
                'rectangle'    => '绘制矩形',
                'circle'       => '绘制圆形',
                'marker'       => '绘制标记',
                'circlemarker' => '绘制圆形标记',
            ],
        ],
        'handlers' => [
            'circle' => [
                'tooltip' => [
                    'start' => '单击并拖动以绘制圆形。',
                ],
                'radius' => '半径',
            ],
            'circlemarker' => [
                'tooltip' => [
                    'start' => '单击地图放置圆形标记。',
                ],
            ],
            'marker' => [
                'tooltip' => [
                    'start' => '单击地图放置标记。',
                ],
            ],
            'polygon' => [
                'tooltip' => [
                    'start' => '单击以开始绘制形状。',
                    'cont'  => '单击以继续绘制形状。',
                    'end'   => '单击第一个点以关闭此形状。',
                ],
            ],
            'polyline' => [
                'error'   => '<strong>错误:</strong> 形状边缘不能交叉！',
                'tooltip' => [
                    'start' => '单击以开始绘制线条。',
                    'cont'  => '单击以继续绘制线条。',
                    'end'   => '单击最后一个点以完成线条。',
                ],
            ],
            'rectangle' => [
                'tooltip' => [
                    'start' => '单击并拖动以绘制矩形。',
                ],
            ],
            'simpleshape' => [
                'tooltip' => [
                    'end' => '释放鼠标以完成绘图。',
                ],
            ],
            'path' => [
                'tooltip' => [
                    'start' => '单击以开始绘制路径。',
                    'cont'  => '单击以继续绘制路径。',
                    'end'   => '单击工具栏上的“完成”按钮以完成路径。',
                ],
            ],
            'brushline' => [
                'tooltip' => [
                    'start' => '单击以开始绘制线条。',
                    'cont'  => '单击并拖动以继续绘制线条。',
                    'end'   => '继续单击/拖动，完成后按工具栏上的“完成”按钮以完成您的线条。',
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
                    'title' => '取消编辑，放弃所有更改',
                    'text'  => '取消',
                ],
                'clearAll' => [
                    'title' => '清除所有图层',
                    'text'  => '清除所有',
                ],
            ],
            'buttons' => [
                'edit'           => '编辑图层',
                'editDisabled'   => '没有可编辑的图层',
                'remove'         => '删除图层',
                'removeDisabled' => '没有可删除的图层',
            ],
        ],
        'handlers' => [
            'edit' => [
                'tooltip' => [
                    'text'    => '拖动手柄或标记以编辑特征。',
                    'subtext' => '单击取消以撤销更改。',
                ],
            ],
            'remove' => [
                'tooltip' => [
                    'text' => '单击要删除的特征。',
                ],
            ],
        ],
    ],

];
