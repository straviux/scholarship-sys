<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemUpdate;

class MarkdownTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemUpdate::create([
            'title' => 'Markdown Test - System Documentation',
            'markdown_content' => <<<'MARKDOWN'
# Scholarship System Update

We are excited to announce **new features** in the scholarship system!

## Key Highlights

### 1. Markdown Support
- Rich text formatting
- Code examples
- Links and images

### 2. Enhanced Notifications
Notifications now support:
- **Bold** and *italic* text
- Ordered and unordered lists
- `inline code` snippets

### 3. Code Blocks

```javascript
const greeting = "Hello, World!";
console.log(greeting);
```

## Important Links

For more information, visit:
- [Documentation](https://example.com/docs)
- [Support Center](https://example.com/support)

---

> **Note**: This is a demo notification showcasing markdown capabilities.

Thank you for using our scholarship system!
MARKDOWN,
            'content' => 'Markdown Test - This is a test notification with markdown content. Please view the full notification to see the rendered markdown.',
            'is_markdown' => true,
            'type' => 'info',
            'priority' => 'normal',
            'is_global' => true,
            'created_by' => 1,
        ]);

        $this->command->info('Markdown test notification created successfully!');
    }
}
