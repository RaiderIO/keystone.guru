function getDistanceSquared(latLng1, latLng2) {
    return Math.pow(latLng1.lat - latLng2.lat, 2) + Math.pow(latLng1.lng - latLng2.lng, 2);
}

function getDistance(latLng1, latLng2) {
    return Math.sqrt(getDistanceSquared(latLng1, latLng2));
}

function getHandlebarsTranslations(){
    let locale = lang.getLocale();
    return lang.messages[locale + '.messages'];
}