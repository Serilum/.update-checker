# Code Mirror of Serilum's Update Checker

This is the code behind the little update checker at `update.serilum.com`. My mods (anything built on [Collective](https://modrinth.com/mod/collective)) ask it once on startup whether a newer version is out, and it answers with the latest version number. That is the whole job.

It is mirrored here so you can read exactly what runs on the server. Nothing hidden.

## What it collects

One thing: a counter. Every time a mod checks, the server adds 1 to a daily tally per mod, Minecraft version, loader, and whether the check was on the latest version. So it ends up knowing things like:

| Mod slug       | Minecraft version  | Mod loader   | Status   | Count |
|----------------|--------------------|--------------|----------|-------|
| tree-harvester | 1.21.1             | Forge        | current  | 380   |
| starter-kit    | 1.20.1             | Fabric       | outdated | 32    |
| double-doors   | 26.1.2             | NeoForge     | current  | 58    |

A status of `current` means you were already on the newest version, `outdated` means a newer one was out, `unknown` means the mod didn't send a version it could read.

That is the entire dataset. I use it to see which versions and loaders people run, and how fast updates actually reach everyone, so I know what is worth keeping updated.

## What it does **not** collect

- No IP addresses
- No usernames, UUIDs or any player data
- No user agent
- Not your actual mod version number. The mod sends it so the server can tell you whether a newer one is out; the server uses it only to tag the check `current` or `outdated`, then throws the number away. You can see that at the bottom of `index.php`.

There is nothing in here that ties a check back to a person. The counter cannot tell who you are. It only knows that *a* check happened.

Your IP does reach the server for the moment it takes to answer the request (it travels through Cloudflare to get there, like a big chunk of the web does), but the code never reads or stores it, and this endpoint keeps no access logs. Only the counter is touched.

## No privacy policy?

There is nothing for one to cover. A privacy policy is there to spell out what personal data a service keeps about you, and this keeps none: no IP, no identity, just the aggregate counter above. The two sections up top are the whole disclosure. If that ever changes, it will change here first.

## Don't want it running?

It is opt-out in the config. Set `enableUpdateChecker = false` in Collective's config and the mod stops contacting the server completely. No request, no count.

## The files

- `minecraft/index.php` answers the version check and bumps the counter
- `minecraft/counter.php` the counter itself, a small SQLite table of those daily counts
- `minecraft/get.php` lets me read the totals back out to graph them (token-protected, not public)
