<section class="container-x mt-12 lg:mt-16">
    <x-section-heading :eyebrow="$section->eyebrow" :title="$section->title ?: $category->name" :href="$category->url" />
    <x-product.scroller :products="$products" />
</section>
