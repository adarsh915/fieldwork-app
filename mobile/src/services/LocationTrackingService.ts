import * as Location from "expo-location";
import * as TaskManager from "expo-task-manager";
import AsyncStorage from "@react-native-async-storage/async-storage";
import {
  uploadBulkLocations,
  type LocationPoint,
  getTrackingSettings,
} from "../api/location";

const LOCATION_TASK_NAME = "background-location-task";
const LOCATION_QUEUE_KEY = "@location_queue";
const TRACKING_STATUS_KEY = "@tracking_active";
const TRACKING_SETTINGS_KEY = "@tracking_settings";
const MAX_QUEUE_SIZE = 500;
const UPLOAD_BATCH_SIZE = 50;

// Define the background task BEFORE class definition
TaskManager.defineTask(LOCATION_TASK_NAME, async ({ data, error }: any) => {
  if (error) {
    console.error("[BackgroundTask] Error:", error.message);
    return;
  }

  if (data) {
    const { locations } = data;
    console.log(`[BackgroundTask] Received ${locations.length} locations`);

    // Queue locations for upload
    try {
      const queueStr = await AsyncStorage.getItem(LOCATION_QUEUE_KEY);
      const queue: LocationPoint[] = queueStr ? JSON.parse(queueStr) : [];

      locations.forEach((loc: any) => {
        queue.push({
          latitude: loc.coords.latitude,
          longitude: loc.coords.longitude,
          recorded_at: new Date(loc.timestamp).toISOString(),
          battery_level: null,
          accuracy: loc.coords.accuracy,
        });

        console.log(
          `[BackgroundTask] Queued location: ${loc.coords.latitude}, ${loc.coords.longitude}`,
        );
      });

      // Keep queue size manageable
      const trimmedQueue = queue.slice(-MAX_QUEUE_SIZE);
      await AsyncStorage.setItem(
        LOCATION_QUEUE_KEY,
        JSON.stringify(trimmedQueue),
      );

      console.log(
        `[BackgroundTask] Queue size: ${trimmedQueue.length} (added ${locations.length})`,
      );

      // Try to upload immediately
      await LocationTrackingService.uploadQueue();
    } catch (err: any) {
      console.error("[BackgroundTask] Failed to queue:", err.message);
    }
  }
});

export class LocationTrackingService {
  /**
   * Start background location tracking using TaskManager
   * Fetches settings from backend and applies them dynamically
   */
  static async startTracking(): Promise<void> {
    try {
      // Request permissions
      const { status: foregroundStatus } =
        await Location.requestForegroundPermissionsAsync();
      if (foregroundStatus !== "granted") {
        throw new Error("Foreground location permission denied");
      }

      const { status: backgroundStatus } =
        await Location.requestBackgroundPermissionsAsync();
      if (backgroundStatus !== "granted") {
        console.warn("[LocationTracking] Background permission not granted");
      }

      // Fetch tracking settings from backend
      let settings = {
        enabled: true,
        time_interval: 60,
        distance_interval: 50,
        accuracy: "high",
      };

      try {
        const response = await getTrackingSettings();
        if (response.success && response.settings) {
          settings = response.settings;
          // Cache settings locally
          await AsyncStorage.setItem(
            TRACKING_SETTINGS_KEY,
            JSON.stringify(settings),
          );
          console.log("[LocationTracking] Settings fetched:", settings);
        }
      } catch (settingsError: any) {
        console.warn(
          "[LocationTracking] Failed to fetch settings, using defaults:",
          settingsError.message,
        );
        // Try to use cached settings
        const cached = await AsyncStorage.getItem(TRACKING_SETTINGS_KEY);
        if (cached) {
          settings = JSON.parse(cached);
          console.log("[LocationTracking] Using cached settings");
        }
      }

      // Check if tracking is enabled
      if (!settings.enabled) {
        console.log(
          "[LocationTracking] Tracking is disabled by admin settings",
        );
        return;
      }

      // Map accuracy setting to Location.Accuracy enum
      let accuracy = Location.Accuracy.High;
      switch (settings.accuracy) {
        case "balanced":
          accuracy = Location.Accuracy.Balanced;
          break;
        case "low":
          accuracy = Location.Accuracy.Low;
          break;
        default:
          accuracy = Location.Accuracy.High;
      }

      // Check if task is already registered
      const isTaskDefined = await TaskManager.isTaskDefined(LOCATION_TASK_NAME);
      if (!isTaskDefined) {
        console.error(
          "[LocationTracking] Background task not defined! This should not happen.",
        );
        return;
      }

      // Start background location updates with dynamic settings
      await Location.startLocationUpdatesAsync(LOCATION_TASK_NAME, {
        accuracy: accuracy,
        timeInterval: settings.time_interval * 1000, // Convert seconds to milliseconds
        distanceInterval: settings.distance_interval, // In meters
        foregroundService: {
          notificationTitle: "AMPL Fieldwork - Location Tracking",
          notificationBody: "Tracking your location for field visits",
          notificationColor: "#667eea",
        },
        pausesUpdatesAutomatically: false,
        activityType: Location.ActivityType.AutomotiveNavigation,
        showsBackgroundLocationIndicator: true,
      });

      // Mark tracking as active
      await AsyncStorage.setItem(TRACKING_STATUS_KEY, "true");

      console.log(
        `✅ [LocationTracking] Started with settings: ${settings.time_interval}s interval, ${settings.distance_interval}m distance, ${settings.accuracy} accuracy`,
      );
    } catch (error: any) {
      console.error("[LocationTracking] Failed to start:", error.message);
      throw error;
    }
  }

  /**
   * Stop background location tracking
   */
  static async stopTracking(): Promise<void> {
    try {
      const hasStarted = await Location.hasStartedLocationUpdatesAsync(
        LOCATION_TASK_NAME,
      );

      if (hasStarted) {
        await Location.stopLocationUpdatesAsync(LOCATION_TASK_NAME);
      }

      // Mark tracking as inactive
      await AsyncStorage.setItem(TRACKING_STATUS_KEY, "false");

      console.log("🛑 [LocationTracking] Stopped");

      // Upload any remaining queued locations
      await this.uploadQueue();
    } catch (error: any) {
      console.error("[LocationTracking] Failed to stop:", error.message);
    }
  }

  /**
   * Check if tracking is supposed to be active
   */
  static async isTrackingActive(): Promise<boolean> {
    try {
      const status = await AsyncStorage.getItem(TRACKING_STATUS_KEY);
      return status === "true";
    } catch {
      return false;
    }
  }

  /**
   * Check if currently tracking
   */
  static async isTracking(): Promise<boolean> {
    try {
      return await Location.hasStartedLocationUpdatesAsync(LOCATION_TASK_NAME);
    } catch {
      return false;
    }
  }

  /**
   * Upload queued locations to server
   */
  static async uploadQueue(): Promise<void> {
    try {
      const queueStr = await AsyncStorage.getItem(LOCATION_QUEUE_KEY);
      if (!queueStr) {
        return;
      }

      const queue: LocationPoint[] = JSON.parse(queueStr);
      if (queue.length === 0) {
        return;
      }

      console.log(
        `⬆️ [LocationUpload] Uploading ${Math.min(queue.length, UPLOAD_BATCH_SIZE)} locations...`,
      );

      // Upload batch
      const batch = queue.slice(0, UPLOAD_BATCH_SIZE);
      const response = await uploadBulkLocations(batch);

      if (response.success) {
        // Remove uploaded locations from queue
        const remaining = queue.slice(UPLOAD_BATCH_SIZE);
        await AsyncStorage.setItem(
          LOCATION_QUEUE_KEY,
          JSON.stringify(remaining),
        );
        console.log(
          `✅ [LocationUpload] Uploaded ${response.inserted} locations (${remaining.length} remaining)`,
        );

        // If there are more, upload again
        if (remaining.length > 0) {
          setTimeout(() => this.uploadQueue(), 2000);
        }
      }
    } catch (error: any) {
      console.error("[LocationUpload] Failed:", error.message);
    }
  }

  /**
   * Get queue size (for debugging)
   */
  static async getQueueSize(): Promise<number> {
    try {
      const queueStr = await AsyncStorage.getItem(LOCATION_QUEUE_KEY);
      if (!queueStr) return 0;
      const queue = JSON.parse(queueStr);
      return queue.length;
    } catch {
      return 0;
    }
  }

  /**
   * Clear location queue
   */
  static async clearQueue(): Promise<void> {
    await AsyncStorage.removeItem(LOCATION_QUEUE_KEY);
    console.log("🗑️ [LocationQueue] Cleared");
  }
}
