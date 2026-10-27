# mud-modules

Modulele custom comune pentru site-urile Drupal 11 care folosesc tema [mud_egov](https://github.com/joSecret/Theme-mud_egov) (mud-egov, age, mud-docs).

Acum conține un singur modul, `mud_core` (pachetul Composer `mud/mud_core`), instalat în site-uri în `web/modules/contrib/mud_core`.

## Ce face mud_core

| Parte | Ce face |
|---|---|
| Block `mud_core_gov_branding` | Bara „Guvernul Republicii Moldova” (componenta `mud_egov:gov-branding`) |
| Block `mud_core_footer_copyright` | Textul de copyright cu anul curent (`mud_egov:footer-copyright`) |
| Block `mud_core_footer_contact` | Logo, telefon, email, adresă și meniu în footer (`mud_egov:footer-contact`) |
| Setări „MUD eGov” în formularul de block | variantă (Default, Dropdown, meniu orizontal/vertical), Bare, nivel titlu, padding, mărime font; salvate ca third-party settings `mud_egov` |
| Element `image_radio` | Radio cu pictograme, folosit la alegerea variantei |
| Formator `media_svg_inline` | Thumbnail media care afișează SVG-ul inline |
| `hook_mud_egov_block_variants()` | Alte module pot adăuga variante pentru un plugin de block |

Pictograma pentru varianta Dropdown apare doar dacă site-ul are `icon_select` și vocabularul `icons`.

## Instalare într-un site

```json
"repositories": [
    {"type": "vcs", "url": "https://github.com/joSecret/mud-modules", "no-api": true}
],
"require": {
    "mud/mud_core": "^1.0"
}
```

## Versiuni

Fiecare versiune e un tag `vX.Y.Z` pe `main`. Site-urile cer `^1.0` și primesc versiunea nouă cu `composer update mud/mud_core`.

## Istoric

- `mud_core` a fost mutat din `joSecret/mud-egov` (`packages/mud_core`) cu tot istoricul.
- `age_general` din `joSecret/age` a fost unit aici: setările de block, `image_radio` și `media_svg_inline`. Block-urile lui duble (GovBranding, FooterCopyright) sunt înlocuite de cele din `mud_core`.
