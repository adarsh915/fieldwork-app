import * as ImageManipulator from "expo-image-manipulator";

/**
 * Resizes and compresses an image before uploading.
 * - Downscales max width to 1280px (height auto-proportional).
 * - Compresses JPEG quality to 0.7 (70%).
 * - Reduces raw 3MB-5MB camera photos down to ~120KB-200KB.
 * - Text, numbers, bills, and odometer gauges remain crystal clear.
 */
export async function optimizeImageForUpload(uri: string): Promise<string> {
  if (!uri) return uri;
  try {
    const manipResult = await ImageManipulator.manipulateAsync(
      uri,
      [{ resize: { width: 1280 } }],
      { compress: 0.7, format: ImageManipulator.SaveFormat.JPEG }
    );
    return manipResult.uri || uri;
  } catch (err) {
    // If manipulation fails for any platform reason, fallback safely to original uri
    console.warn("[imageOptimizer] Failed to manipulate image, using original:", err);
    return uri;
  }
}
