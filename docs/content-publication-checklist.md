# Qurba content publication checklist

## Word-by-word (Quran.com / Quran Foundation)

- [ ] Identify the exact dataset, version, acquisition method and owner.
- [ ] Confirm applicable license/API terms or obtain written permission for the intended use (including offline caching and redistribution).
- [ ] Record attribution and any synchronization requirements.
- [ ] Review accuracy and approve the source in Qurba's content administration.
- [ ] Only after all checks, set `QURBA_WORD_BY_WORD_PUBLIC_ENABLED=true` in the production `.env` and rebuild config cache using `php artisan config:cache`.
- [ ] Test `/api/v1/quran/extras/words/1` (404 when disabled; expected content when enabled and source approved).
- [ ] Verify the Quran reader handles the disabled state without a broken interface.

The default is **false**. Local development continues to permit preview of unapproved sources; production requires both source approval and the publication flag.

## Tajweed

- [ ] Verify exact source and attribution text, including quran-tajweed by Collin Fair (CC BY 4.0) and applicable Tanzil conditions.
- [ ] Provide credits, license links and any required change notices.
- [ ] Confirm Quran text is unchanged and Tajweed color legend is understandable.

## Deployment and audio

- [ ] Back up database and uploaded content; check `git status --short` before deployment.
- [ ] Verify migrations, `npm run build`, routes and the frontend after deployment.
- [ ] Inspect `/api/v1/audio-manifest`; code support does not imply that MP3 recordings are installed.
- [ ] Test on mobile/PWA after clearing stale service worker caches.

This checklist is an engineering gate, not a determination that third-party content is licensed.
