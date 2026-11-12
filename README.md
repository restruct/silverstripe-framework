# silverstripe/framework: CMS 5 security backports

*Maintained by [Restruct](https://github.com/restruct). If this fork saves you time, you can [support ongoing maintenance](https://github.com/sponsors/restruct).*

This is a fork of [silverstripe/silverstripe-framework](https://github.com/silverstripe/silverstripe-framework) for sites that still run Silverstripe CMS 5. It adds the medium-severity security fixes that upstream released for CMS 6 only, on top of upstream `5.4.30`, and changes nothing else. The package name stays `silverstripe/framework`.

> **What to expect from this fork**
>
> - **Security fixes only.** The aim is to backport the fix for every published advisory against this package, at any severity, for as long as CMS 5 is in partial support upstream. This is best effort: there is no guaranteed response time.
> - **No features, no general bug fixes.** Requests to port those will normally be declined. Use CMS 6 for them.
> - **It may be discontinued at any time.** The fork exists mainly to keep our own projects secure until they move to CMS 6. Anyone is welcome to use it as is, under the same license and with no warranty. Help is appreciated: a report of a missed advisory or of a regression in a backport, or a pull request with a backport.
> - **Not affiliated with or endorsed by Silverstripe.** The fixes are upstream's own work; any mistake in backporting them is ours. Do not report problems with this fork to Silverstripe.
> - **Found a new vulnerability?** Do not open a public issue. If it is in Silverstripe's code, report it to [security@silverstripe.org](mailto:security@silverstripe.org) as upstream asks. If it is in a change this fork made, email [dev@restruct.nl](mailto:dev@restruct.nl).
> - **The way out is CMS 6.** When a site upgrades, remove the fork and feed repositories (and the Packagist exclude) from the project's `composer.json`.

## Quick start

For a CMS 5.4 project. Your `require` section stays as it is: the forks keep the upstream package names.

1. Add this to your project's `composer.json`. Keep all four forks even if the project does not use one of them
   (an unused entry costs nothing), and keep the order: forks first, then the feed, then Packagist.

   ```json
   "repositories": [
       {"type": "vcs", "url": "https://github.com/restruct/silverstripe-framework", "only": ["silverstripe/framework"]},
       {"type": "vcs", "url": "https://github.com/restruct/silverstripe-versioned", "only": ["silverstripe/versioned"]},
       {"type": "vcs", "url": "https://github.com/restruct/silverstripe-assets", "only": ["silverstripe/assets"]},
       {"type": "vcs", "url": "https://github.com/restruct/silverstripe-userforms", "only": ["silverstripe/userforms"]},
       {"type": "composer", "url": "https://restruct.github.io/ss5-advisories"},
       {"type": "composer", "url": "https://repo.packagist.org",
        "exclude": ["silverstripe/framework", "silverstripe/versioned", "silverstripe/assets", "silverstripe/userforms", "silverstripe/cms"]},
       {"packagist.org": false}
   ]
   ```

   If the project already has a `repositories` section, add these entries to it, ahead of any other entry that could
   provide these packages.

2. Remove any `config.policy.advisories.ignore-id` (or `audit.ignore`) entries for the advisories in the table
   below. The feed handles them per version; an ignore would silence them for every version.

3. Update and check:

   ```bash
   composer update silverstripe/framework silverstripe/versioned silverstripe/assets silverstripe/userforms silverstripe/cms
   composer show | grep -E '^silverstripe/(framework|versioned|assets|userforms|cms) '
   composer audit
   ```

   `composer show` should list the `-pN` versions (`5.4.30-p1`, `2.4.8-p1`, `2.4.6-p1`, `6.4.10-p1` if the project uses userforms) and cms from the 5.4 line.
   `composer audit` should not list any of the advisories this fork set handles. Then run `dev/build flush=1`.

4. Using silverstripe/mfa? Its advisory (CVE-2026-55186) is fixed by
   [restruct/silverstripe-mfa-bundle](https://github.com/restruct/silverstripe-mfa-bundle), not here. With the bundle
   installed at 1.9.1 or later (require `"restruct/silverstripe-mfa-bundle": "^1.9.1"`), ignore that one advisory
   with the reason:
   `"config": {"policy": {"advisories": {"ignore-id": {"PKSA-xxk3-rjxm-jftv": "fixed by restruct/silverstripe-mfa-bundle >= 1.9.1"}}}}`.

To stop using the forks (for example when moving to CMS 6), remove these repository entries and the mfa ignore again.

## Why CMS 5 needs this

CMS 5.4 is in partial support: upstream fixes only security issues with a CVSS score of 7.0 or higher on it ([release policy](https://github.com/silverstripe/developer-docs/blob/6/en/12_Project_Governance/05_Release_policy.md#partial-support)). The medium-severity fixes of June and October 2026 were released for CMS 6 only, so a fully updated CMS 5 site still carries them. The right fix is to upgrade to CMS 6; this fork is for the time a site cannot do that yet.

The fixes are upstream's own commits, cherry-picked with `git cherry-pick -x` onto branch `5.4-restruct`. Where a commit did not apply as is, the commit message has a "Backport note" saying what was adapted and why. Tests were adapted to PHPUnit 9.6 (CMS 5) in separate commits and no assertion was weakened. The one existing expectation that changed is the one upstream changed with the same fix (framework, CVE-2026-88987: `data:text/xml` is now removed), and it is in a commit of its own.

Companion forks, for the other packages with backported fixes: [restruct/silverstripe-versioned](https://github.com/restruct/silverstripe-versioned) (`silverstripe/versioned`), [restruct/silverstripe-assets](https://github.com/restruct/silverstripe-assets) (`silverstripe/assets`), [restruct/silverstripe-userforms](https://github.com/restruct/silverstripe-userforms) (`silverstripe/userforms`).

## Advisories against the CMS 5 stack, and what was done with each

All advisories published for these packages in 2026 that affect their latest CMS 5 releases. Every fork lists the full set, so the whole picture is visible from any of them.

| Package | Advisory | Title | Status | Upstream fix |
|---|---|---|---|---|
| **framework** | SS-2026-004<br>PKSA-s9ft-gn8c-nz8s | Password reset token account takeover | Backported in framework 5.4.30-p1 | [`06e668db01`](https://github.com/silverstripe/silverstripe-framework/commit/06e668db01050895bb7e767fbf0b368c1081f479) |
| **framework** | CVE-2026-88989<br>PKSA-byd1-vwr7-j3ng | Auth bypass GridFieldDetailForm | Backported in framework 5.4.30-p1 | [`693c948bae`](https://github.com/silverstripe/silverstripe-framework/commit/693c948bae13dd2a854489baa575123ca3e60155) |
| **framework** | CVE-2026-54720<br>PKSA-x6tz-s6v3-ynk3 | XSS attack through media embed | Backported in framework 5.4.30-p1 | [`1bcb02adfc`](https://github.com/silverstripe/silverstripe-framework/commit/1bcb02adfc365c6436dc26ab2f6dd32d97f3979b) |
| **framework** | CVE-2026-91110<br>PKSA-mxm6-h6dq-vmtc | XSS using control bytes | Backported in framework 5.4.30-p1 | [`dbf965166d`](https://github.com/silverstripe/silverstripe-framework/commit/dbf965166da909739a197c95b27dd9ee2fde93c9) |
| **framework** | CVE-2026-88987<br>PKSA-3ddc-hhgt-bqh9 | XSS via XHTML data URI | Backported in framework 5.4.30-p1. As upstream, `data:text/xml` is now removed as well | [`cea9c46eba`](https://github.com/silverstripe/silverstripe-framework/commit/cea9c46eba4be31a06adf6980494623385e1296d) |
| **framework** | CVE-2026-88988<br>PKSA-v91s-x13r-xsps | XSS via SVG data URI | Backported in framework 5.4.30-p1 | [`9b61e6d04d`](https://github.com/silverstripe/silverstripe-framework/commit/9b61e6d04da1a3183c4ef4081cb64fd31ae0701f) |
| **framework** | CVE-2026-91112<br>PKSA-r36n-y55f-yj29 | CMS_ACCESS_SecurityAdmin privilege escalation | Backported in framework 5.4.30-p1 | [`bb4d04a498`](https://github.com/silverstripe/silverstripe-framework/commit/bb4d04a498c34e21e428d029c4ce82bbdcce31b1) |
| **framework** | CVE-2026-91114<br>PKSA-2w11-jnmw-7vn5 | Stored XSS via unescaped permission in PermissionCheckboxSetField | Backported in framework 5.4.30-p1 | [`3ac8440a3a`](https://github.com/silverstripe/silverstripe-framework/commit/3ac8440a3accc04e5dbca1a41513d628920e58c2) |
| **framework** | CVE-2026-91113<br>PKSA-2h3f-jtk1-8f25 | User-controlled ClassName allows unauthorized record type change | Backported in framework 5.4.30-p1 | [`8b6e9c4514`](https://github.com/silverstripe/silverstripe-framework/commit/8b6e9c4514cbe37feeaf9ddddf796a3109448187) |
| **framework** | CVE-2026-91115<br>PKSA-bqyr-kn4k-vx5h | RCE via i18nTextCollectorTask writer parameter | Not applicable: the advisory affects framework >=6.0.0 only | - |
| versioned | CVE-2026-55779<br>PKSA-nksq-cxj8-zb32 | XSS in archive admin restore | Backported in versioned 2.4.8-p1 | [`6e30a2cf8d`](https://github.com/silverstripe/silverstripe-versioned/commit/6e30a2cf8d4b9233690464da61bd0fc4d3e92952) |
| userforms | CVE-2026-91111<br>PKSA-vyds-y4jb-5ff4 | Uploaded XML XSS | Backported in **assets** 2.4.6-p1: the fix is in silverstripe/assets. Like the upstream fix, userforms 6.4.10-p1 requires the patched assets release (`^2.4.6-p1`) | [`88750f95e8`](https://github.com/silverstripe/silverstripe-assets/commit/88750f95e8eafcae6ce41bc131056ca75d960d7f) |
| userforms | CVE-2026-88992<br>PKSA-w1yx-v3g8-nw8g | Userforms CSV export restricted file access | Backported in userforms 6.4.10-p1 | [`4d752ea8f3`](https://github.com/silverstripe/silverstripe-userforms/commit/4d752ea8f3e26a6423e90b7d07c92ae69f96ec2c) |
| userforms | SS-2026-005<br>PKSA-r5k2-9jwp-nhhh | XSS in required field label | Backported in userforms 6.4.10-p1 | [`bc96ea4af2`](https://github.com/silverstripe/silverstripe-userforms/commit/bc96ea4af2d5eb026f739c4b36a36c2244caca57) |
| mfa | CVE-2026-55186<br>PKSA-xxk3-rjxm-jftv | Can bypass MFA during password reset flow | Not in these forks. Upstream disclosed it without a fix for CMS 5; [restruct/silverstripe-mfa-bundle](https://github.com/restruct/silverstripe-mfa-bundle) fixes it from 1.9.1 | - |
| cms | CVE-2026-54717<br>PKSA-pjvm-vnw2-n3m2 | XSS in breadcrumbs in page list view | Not applicable: in cms 5.4 the list view builds the breadcrumb line with `SiteTree::Breadcrumbs()`, which escapes titles. Checked by rendering the list view for a parent page titled `<img src=x onerror=alert(1)>`: the line came out as `&lt;img src=x onerror=alert(1)&gt;/`. The unescaped loop the upstream fix replaces first appears in cms 6.0 | [`62f9912baa`](https://github.com/silverstripe/silverstripe-cms/commit/62f9912baa18c80304f3fa8b6eca71bb5dc2d21e) |

Already fixed by upstream in a CMS 5 release (update to that release or later; nothing to backport):

- assets 2.4.5: CVE-2026-24749, DBFile permission bypass
- assets 2.4.6: SS-2026-003, RCE via renamed uploaded file
- userforms 6.4.9: CVE-2026-54721, Remote code execution via userforms email subject
- userforms 6.4.10: CVE-2026-88991, RCE in email subject field via template execution
- userforms 6.4.10: SS-2026-001, Template injection RCE in userforms
- userforms 6.4.10: SS-2026-006, RCE via template string escaping in userforms email subject

## Installation details

The four forks are installed together, with the advisory feed that goes with them (see Quick start above; [restruct/ss5-advisories](https://github.com/restruct/ss5-advisories#use-it-in-a-project) explains each line). The forks are listed first and limited with `only` to their own package, and Packagist is told not to serve these package names.

Releases are tagged `<upstream version>-pN` (`5.4.30-p1` is the first). Composer treats that as a stable version above `5.4.30` and below the next upstream release, so existing constraints such as `^5.4` keep matching.

Do not set `"canonical": false` on the fork repositories. With the forks canonical, a later upstream release on Packagist cannot silently replace the patched build; each upstream release is merged in here and tagged as `<version>-p1`. A scheduled job on this repository opens an issue when upstream tags a new release on this line.

### Why the advisory feed

Packagist records advisories by package name and version range and does not know about this fork, so a patched tag still falls inside the published ranges: `composer audit` reports the advisories above and Composer refuses to install it. The feed carries the same advisories with the patched tags taken out of the ranges, and nothing else changed, so an advisory that is not backported still blocks and reports. It rebuilds itself from Packagist every day.

## Maintenance

Maintained, best effort, while CMS 5 is in partial support upstream, or until upstream releases these fixes for CMS 5, whichever comes first (see "What to expect" above). Report a missed advisory or a regression in a backport in this repository's issues. For everything else about the package itself, see the [upstream repository](https://github.com/silverstripe/silverstripe-framework).

Before each tag, the package's own test suite is run on the lowest and highest PHP version CMS 5 supports here (8.1 and 8.3), against an unmodified CMS 5.4 install, and compared with the same run on the upstream release it is based on. Tags are never moved or rewritten: a correction gets the next `-pN`.

## License

BSD-3-Clause, as upstream. See [LICENSE](LICENSE).
