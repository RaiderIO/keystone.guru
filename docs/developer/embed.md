# Route embedding options
If you're embedding a route on your website, you have a few options available to customize the appearance/behaviour of Keystone.guru.

## Usage
https://keystone.guru/abcd1234/embed?pulls=1&pullsDefaultState=0 etc.

## Reference
|Name|Type|Description|
|----|-----|-----------|
|pulls|**boolean**|`0` or `1`. When `1`, will allow the display of the pulls sidebar. If not passed, the pulls sidebar will be available.|
|pullsDefaultState|**boolean**|`0` or `1`. When `1`, the pulls sidebar will be shown upon page load. When `0`, the pulls sidebar will be hidden upon page load. If not passed, the pulls sidebar will be hidden from view.|
|pullsHideOnMove|**boolean**|`0` or `1`. When `1`, the pulls sidebar will automatically hide if the user moves the map around. When `0`, the pulls sidebar will always remain open. By default the pulls sidebar will remain open on desktop, and automatically hide when a mobile device is detected.|
|enemyinfo|**boolean**|`0` or `1`. When `1`, when the user performs a mouseover on an enemy, the enemy info pane will be shown. When `0`, the enemy info pane will not be shown.|

