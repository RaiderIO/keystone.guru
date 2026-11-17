-- keystone.guru open-world NPC portraits, batch {batch} of {batch_count}
local npcIds = {
{ids_block}}

local batchLabel = "BATCH {batch}"
local resX = {res_x}
local resY = {res_y}
local portraitSize = 64
local tilesX = floor(resX / portraitSize)
local scale = 768 / resY / UIParent:GetScale()

local holder = CreateFrame("Frame", nil, UIParent)
holder:SetAllPoints(UIParent)
holder:SetFrameStrata("FULLSCREEN_DIALOG")

local label = holder:CreateFontString(nil, "OVERLAY", "GameFontNormalHuge")
label:SetPoint("BOTTOMRIGHT", UIParent, "BOTTOMRIGHT", -40, 40)
label:SetText(batchLabel)

-- Each NPC is loaded into a model hidden behind its tile; the model yields the display id the portrait is drawn from
local models = {}
local tiles = {}
for i = 1, #npcIds do
   local posX = (i - 1) % tilesX * portraitSize
   local posY = -floor((i - 1) / tilesX) * portraitSize

   local model = CreateFrame("PlayerModel", nil, UIParent)
   model:SetFrameStrata("BACKGROUND")
   model:SetSize(portraitSize, portraitSize)
   model:SetScale(scale)
   model:SetPoint("TOPLEFT", UIParent, "TOPLEFT", posX, posY)
   model:SetCreature(npcIds[i])
   models[i] = model

   local tile = CreateFrame("Frame", nil, holder)
   tile:SetSize(portraitSize, portraitSize)
   tile:SetScale(scale)
   tile:SetPoint("TOPLEFT", UIParent, "TOPLEFT", posX, posY)
   local background = tile:CreateTexture(nil, "BACKGROUND", nil, 1)
   background:SetColorTexture(0, 0, 0)
   background:SetAllPoints(tile)
   local portrait = tile:CreateTexture(nil, "BACKGROUND", nil, 2)
   portrait:SetAllPoints(tile)
   tile.portrait = portrait
   tiles[i] = tile
end

-- The client loads an uncached NPC asynchronously, so it is asked twice before its display id is read
local elapsedTotal = 0
local step = 0
holder:SetScript("OnUpdate", function(self, elapsed)
   elapsedTotal = elapsedTotal + elapsed
   if step == 0 and elapsedTotal >= 3 then
      step = 1
      for i = 1, #npcIds do
         models[i]:SetCreature(npcIds[i])
      end
   elseif step == 1 and elapsedTotal >= 6 then
      step = 2
      local resolved = 0
      for i = 1, #npcIds do
         local displayId = models[i]:GetDisplayInfo() or 0
         if displayId > 0 then
            SetPortraitTextureFromCreatureDisplayID(tiles[i].portrait, displayId)
            resolved = resolved + 1
         end
         models[i]:Hide()
      end
      print(batchLabel .. ": " .. resolved .. "/" .. #npcIds .. " NPCs resolved")
   elseif step == 2 and elapsedTotal >= 8 then
      step = 3
      Screenshot()
   elseif step == 3 and elapsedTotal >= 10 then
      self:SetScript("OnUpdate", nil)
      self:Hide()
      print(batchLabel .. ": screenshot taken, ready for the next one")
   end
end)
