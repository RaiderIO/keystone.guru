local displayIds = {
111,
155,
}

local resX = 2560
local resY = 1440
local portraitSizeX = 64
local portraitSizeY = 64
local maxTiles = 100

local tilesX = floor(resX / portraitSizeX)
local tilesY = floor(resY / portraitSizeY)
local numTilesNeeded = table.getn(displayIds)
local numTilesAvailable = min(maxTiles, tilesX * tilesY)
local numScreenshotsRequired = ceil(numTilesNeeded / numTilesAvailable)
local numFramesNeeded = min(numTilesNeeded, numTilesAvailable)

local frames = {}

for i=1,numFramesNeeded do
   local posX = (i - 1) % tilesX * portraitSizeX
   local posY = -floor((i - 1) / tilesX) * portraitSizeY

   local frame = CreateFrame("Frame", nil, UIParent)
   frame:SetSize(portraitSizeX, portraitSizeY)
   frame:SetScale(768 / resY / UIParent:GetScale())
   frame:SetPoint("TOPLEFT", UIParent, "TOPLEFT", posX, posY)

   local background = frame:CreateTexture(nil, "BACKGROUND")
   background:SetColorTexture(0, 0, 0)
   background:SetAllPoints(frame)
   background:SetDrawLayer("BACKGROUND", 1)

   local portrait = frame:CreateTexture(nil, "BACKGROUND")
   portrait:SetAllPoints(frame)
   portrait:SetDrawLayer("BACKGROUND", 2)

   frame.portrait = portrait

   table.insert(frames, frame)
end

local updateInterval = 2
local timeSum = updateInterval
local screenshotsDone = 0
local screenshotDone = true

local function onUpdate(self, elapsed)
   timeSum = timeSum + elapsed
   if timeSum < updateInterval then
      return
   end

   if not screenshotDone then
      Screenshot()
      screenshotDone = true
      screenshotsDone = screenshotsDone + 1
      if screenshotsDone >= numScreenshotsRequired then
         self:SetScript("OnUpdate", nil)
      end
      return
   end

   screenshotDone = false
   timeSum = 0

   local portraitsRemaining = numTilesNeeded - (screenshotsDone * numTilesAvailable)
   local portraitStartIndex = screenshotsDone * numTilesAvailable + 1
   local portraitEndIndex = portraitStartIndex + min(portraitsRemaining, numTilesAvailable) - 1

   for i=portraitStartIndex, portraitEndIndex do
      local displayId = displayIds[i]
      local displayFrame = frames[i - portraitStartIndex + 1]
      SetPortraitTextureFromCreatureDisplayID(displayFrame.portrait, displayId)
   end

   for i=portraitEndIndex-portraitStartIndex + 2,numFramesNeeded do
      frames[i]:Hide()
   end
end

local eventFrame = CreateFrame("Frame", nil, UIParent)
eventFrame:SetScript("OnUpdate", onUpdate)
