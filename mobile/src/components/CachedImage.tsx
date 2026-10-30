import React from "react";
import { Image, type ImageProps } from "expo-image";

export interface CachedImageProps extends ImageProps {
  resizeMode?: "cover" | "contain" | "stretch" | "center";
}

/**
 * Reusable cached image component powered by expo-image.
 * - Stores downloaded images on disk cache so subsequent views load instantly without network hit.
 * - Hardware accelerated decoding and memory cache.
 * - Smooth fade-in transition (150ms).
 */
export default function CachedImage({
  style,
  cachePolicy = "disk",
  transition = 150,
  contentFit,
  resizeMode,
  ...rest
}: CachedImageProps) {
  const resolvedContentFit = contentFit || (resizeMode as any) || "cover";

  return (
    <Image
      style={style}
      cachePolicy={cachePolicy}
      transition={transition}
      contentFit={resolvedContentFit}
      {...rest}
    />
  );
}
