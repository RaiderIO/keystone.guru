#!/usr/bin/env node
// Refreshes the Codex usage cache the status line reads (.claude/statusline/statusline.sh).
// Run in the background, on a stale-cache trigger — never in the status line's hot path, since
// starting the Codex app-server broker can take the better part of a second on a cold start.
// Machine-global (cwd = $HOME), not per-worktree: Codex usage is an account-level quota, not tied
// to any one repo checkout, and this keeps every session sharing one broker instead of spawning
// one per worktree just to poll usage.
import fs from "node:fs";
import os from "node:os";
import path from "node:path";

const CACHE_FILE = path.join(os.homedir(), ".claude", "statusline", "codex-usage-cache.json");

function writeCache(payload) {
  const tmp = `${CACHE_FILE}.tmp-${process.pid}`;
  fs.mkdirSync(path.dirname(CACHE_FILE), { recursive: true });
  fs.writeFileSync(tmp, JSON.stringify({ fetchedAt: Math.floor(Date.now() / 1000), ...payload }));
  fs.renameSync(tmp, CACHE_FILE);
}

function compareVersions(a, b) {
  const partsA = a.split(".").map(Number);
  const partsB = b.split(".").map(Number);
  for (let i = 0; i < Math.max(partsA.length, partsB.length); i++) {
    const diff = (partsA[i] || 0) - (partsB[i] || 0);
    if (diff !== 0) {
      return diff;
    }
  }
  return 0;
}

function resolveAppServerModule() {
  const pluginDir = path.join(os.homedir(), ".claude", "plugins", "cache", "openai-codex", "codex");
  const roots = fs
    .readdirSync(pluginDir, { withFileTypes: true })
    .filter((e) => e.isDirectory())
    .map((e) => e.name)
    .sort(compareVersions);
  const latest = roots[roots.length - 1];
  if (!latest) {
    throw new Error("openai-codex plugin not found");
  }
  return path.join(pluginDir, latest, "scripts", "lib", "app-server.mjs");
}

async function main() {
  let CodexAppServerClient;
  try {
    ({ CodexAppServerClient } = await import(resolveAppServerModule()));
  } catch (error) {
    writeCache({ available: false, error: `plugin unavailable: ${error.message}` });
    return;
  }

  let client;
  try {
    client = await CodexAppServerClient.connect(os.homedir(), {});
    const response = await client.request("account/rateLimits/read", {});
    const snapshot = response?.rateLimits ?? null;
    if (!snapshot) {
      writeCache({ available: false, error: "no rateLimits in response" });
      return;
    }
    writeCache({
      available: true,
      planType: snapshot.planType ?? null,
      primary: snapshot.primary ?? null,
      secondary: snapshot.secondary ?? null
    });
  } catch (error) {
    writeCache({ available: false, error: error?.message ?? String(error) });
  } finally {
    await client?.close().catch(() => {});
  }
}

await main();
