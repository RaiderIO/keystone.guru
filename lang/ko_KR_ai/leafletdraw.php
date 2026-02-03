<?php

return [

    'draw' => [
        'toolbar'  => [
            'actions' => [
                'title' => '그리기 취소',
                'text'  => '취소',
            ],
            'finish'  => [
                'title' => '그리기 완료',
                'text'  => '완료',
            ],
            'undo'    => [
                'title' => '마지막 점 삭제',
                'text'  => '마지막 점 삭제',
            ],
            'buttons' => [
                'polyline'     => '폴리라인 그리기',
                'polygon'      => '다각형 그리기',
                'rectangle'    => '사각형 그리기',
                'circle'       => '원을 그리기',
                'marker'       => '마커 그리기',
                'circlemarker' => '원형 마커 그리기',
            ],
        ],
        'handlers' => [
            'circle'       => [
                'tooltip' => [
                    'start' => '원을 그리려면 클릭하고 드래그하세요.',
                ],
                'radius'  => '반지름',
            ],
            'circlemarker' => [
                'tooltip' => [
                    'start' => '원형 마커를 놓으려면 지도를 클릭하세요.',
                ],
            ],
            'marker'       => [
                'tooltip' => [
                    'start' => '마커를 놓으려면 지도를 클릭하세요.',
                ],
            ],
            'polygon'      => [
                'tooltip' => [
                    'start' => '모양을 그리려면 클릭하세요.',
                    'cont'  => '모양을 계속 그리려면 클릭하세요.',
                    'end'   => '이 모양을 닫으려면 첫 번째 점을 클릭하세요.',
                ],
            ],
            'polyline'     => [
                'error'   => '<strong>오류:</strong> 모양의 가장자리가 교차할 수 없습니다!',
                'tooltip' => [
                    'start' => '선을 그리려면 클릭하세요.',
                    'cont'  => '선을 계속 그리려면 클릭하세요.',
                    'end'   => '선을 마무리하려면 마지막 점을 클릭하세요.',
                ],
            ],
            'rectangle'    => [
                'tooltip' => [
                    'start' => '사각형을 그리려면 클릭하고 드래그하세요.',
                ],
            ],
            'simpleshape'  => [
                'tooltip' => [
                    'end' => '그리기를 마치려면 마우스를 놓으세요.',
                ],
            ],
            'path'         => [
                'tooltip' => [
                    'start' => '경로를 그리려면 클릭하세요.',
                    'cont'  => '경로를 계속 그리려면 클릭하세요.',
                    'end'   => '경로를 완성하려면 툴바의 \'완료\' 버튼을 클릭하세요.',
                ],
            ],
            'brushline'    => [
                'tooltip' => [
                    'start' => '선을 그리려면 클릭하세요.',
                    'cont'  => '선을 계속 그리려면 클릭하고 드래그하세요.',
                    'end'   => '계속 클릭/드래그하고, 완료되면 툴바의 \'완료\' 버튼을 눌러 선을 완성하세요.',
                ],
            ],
        ],
    ],
    'edit' => [
        'toolbar'  => [
            'actions' => [
                'save'     => [
                    'title' => '변경 사항 저장',
                    'text'  => '저장',
                ],
                'cancel'   => [
                    'title' => '편집 취소, 모든 변경 사항 취소',
                    'text'  => '취소',
                ],
                'clearAll' => [
                    'title' => '모든 레이어 지우기',
                    'text'  => '모두 지우기',
                ],
            ],
            'buttons' => [
                'edit'           => '레이어 편집',
                'editDisabled'   => '편집할 레이어 없음',
                'remove'         => '레이어 삭제',
                'removeDisabled' => '삭제할 레이어 없음',
            ],
        ],
        'handlers' => [
            'edit'   => [
                'tooltip' => [
                    'text'    => '기능을 편집하려면 핸들이나 마커를 드래그하세요.',
                    'subtext' => '변경 사항을 취소하려면 취소를 클릭하세요.',
                ],
            ],
            'remove' => [
                'tooltip' => [
                    'text' => '기능을 제거하려면 클릭하세요.',
                ],
            ],
        ],
    ],

];
