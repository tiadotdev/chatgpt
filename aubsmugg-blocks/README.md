##### aubsmugg-blocks

# Codebase of all blocks developed by Aubs & Mugg for client sites
**Download the entire zip, remove the blocks you don't need and run `{ npm run build }` in terminal**

*If a new block is added during development, download entire zip folder, copy block folder into src/blocks directory, run `{ npm run build }` in terminal and then reupload files to this repo.*

### Current Block List
- Card (Image, Header, Paragraph, Button)




======== DEV NOTES ========
1. `{ aubsmugg-blocks.php }` should only be used for plugin wide settings
2. Each block folder should be completely self contained in where it can be downloaded and used independently including all plugins, styling and JS
3. Update repo each time a new block is added
4. That's it for now



======== RELEASE NOTES ========
```
{
  Current Version: 0.1.0
  Release Date: October 28th, 2022
  PHP Version: 8.0
  WP Version: 6.02
}
```


======== CHANGELOG ========
### Verion 0.1.0
- created plugin framework
- `{ aubsmugg-blocks.php }` is set up to scan src/blocks directory and require the php files of each block, no manual updating of this file required
